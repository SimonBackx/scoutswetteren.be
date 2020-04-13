<?php
namespace Pirate\Sails\Webshop\Admin;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Pirate\Sails\Webshop\Models\Order;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class OrderSheetExcel extends Page
{
    public function __construct($order_sheet)
    {
        $this->order_sheet = $order_sheet;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getPreviousLetter($letter)
    {
        if (strlen($letter) == 2) {
            $last = substr($letter, -1);
            if ($last == 'A') {
                return 'Z';
            }
            return 'A' . $this->getPreviousLetter(substr($letter, -1));
        }
        return chr(ord($letter) - 1);
    }

    public function getContent()
    {

        $products = $this->order_sheet->products;
        $filters = [];

        // Create filters for all possible combinations for products
        foreach ($products as $product) {
            $add = function ($product, $price) use (&$filters) {
                $optionset_counter = [];

                foreach ($product->optionsets as $index => $optionset) {
                    $optionset_counter[$index] = 0;
                }

                $addCurrentCounters = function () use (&$filters, &$price, &$product, &$optionset_counter) {
                    $options = [];
                    foreach ($product->optionsets as $_index => $optionset) {
                        $index = $optionset_counter[$_index];
                        if ($index >= count($optionset->options)) {
                            $options[] = null;
                            continue;
                        }
                        $options[] = $optionset->options[$index];
                    }
                    $filters[] = (object) [
                        'product' => $product,
                        'price' => $price ?? null,
                        'options' => $options,
                    ];
                };

                $increaseOption = null;
                $increaseOption = function ($optionsetIndex) use (&$product, &$increaseOption, &$optionset_counter) {
                    if ($optionsetIndex < 0) {
                        return false;
                    }

                    $optionset_counter[$optionsetIndex]++;
                    if ($optionset_counter[$optionsetIndex] > count($product->optionsets[$optionsetIndex]->options)) {
                        $optionset_counter[$optionsetIndex] = 0;

                        if (!$increaseOption($optionsetIndex - 1)) {
                            return false;
                        }

                    }
                    return true;
                };

                $addCurrentCounters();
                while ($increaseOption(count($product->optionsets) - 1) === true) {
                    $addCurrentCounters();
                }
            };

            foreach ($product->prices as $price) {
                $add($product, $price);
            }
            if (count($product->prices) > 1) {
                $add($product, null);
            }
        }

        $orders = Order::getByOrderSheet($this->order_sheet->id);

        /// Start with filling in the filters on top
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $letter = 'A';
        $sheet->getColumnDimension($letter)->setWidth(30);

        $startIndex = 1;

        foreach ($filters as $filter_index => $filter) {
            if (substr($letter, -1) == 'Z') {
                $letter = 'A' . substr($letter, 0, strlen($letter) - 1) . 'A';
            } else {
                $last = substr($letter, -1);
                $last++;
                $letter = substr($letter, 0, strlen($letter) - 1) . $last;
            }

            $index = 1;
            $sheet->getColumnDimension($letter)->setWidth(15);
            $sheet->setCellValue($letter . $index, $filter->product->name);

            $merge = function ($key, $index) use (&$sheet, $filters, $filter, $letter, $filter_index) {
                $end = $filter_index - 1;

                if (!isset($filters[$end])) {
                    return;
                }

                if ($filters[$end]->$key !== $filter->$key || $filter_index == count($filters) - 1) {

                    if ($filters[$end]->$key !== $filter->$key) {
                        $endLetter = $this->getPreviousLetter($letter);
                    } else {
                        $endLetter = $letter;
                        $end = $filter_index;
                    }
                    $start = $end;
                    $startLetter = $endLetter;
                    while ($start > 0) {
                        $start--;
                        if ($filters[$start]->$key !== $filters[$end]->$key) {
                            $start++;
                            break;
                        }
                        $startLetter = $this->getPreviousLetter($startLetter);
                    }

                    if ($endLetter !== $startLetter) {
                        $sheet->mergeCells($startLetter . $index . ':' . $endLetter . $index);
                    }
                }
            };

            $merge('product', $index);

            if (count($filter->product->prices) > 1) {
                $index++;
                if (isset($filter->price)) {
                    $sheet->setCellValue($letter . ($index), $filter->price->name);
                } else {
                    $sheet->setCellValue($letter . ($index), 'Totaal');
                }
                $merge('price', $index);
            }

            foreach ($filter->options as $option_index => $option) {
                $index++;
                if (isset($option)) {
                    $sheet->setCellValue($letter . ($index), $option->name);
                } else {
                    $sheet->setCellValue($letter . ($index), 'Totaal');
                }
            }

            if ($index + 1 > $startIndex) {
                $startIndex = $index + 1;
            }
        }

        foreach ($orders as $index => $order) {
            $letter = 'A';
            $sheet->setCellValue($letter . ($index + $startIndex), $order->user->firstname . ' ' . $order->user->lastname . "\n" . $order->user->phone . "\n" . $order->user->mail . "\n". (!empty($order->user->address) ? ( $order->user->address.', '.$order->user->zipcode.' '.$order->user->city. "\n") : '') . $order->getPaymentName() . ($order->isPaid() ? '' : " ! Niet betaald !"));
            $sheet->getStyle($letter . ($index + $startIndex))->getAlignment()->setWrapText(true);

            foreach ($filters as $filter) {
                $letter++;

                $count = 0;
                foreach ($order->items as $item) {
                    if (isset($filter->product) && $filter->product->id != $item->product->id) {
                        continue;
                    }
                    if (isset($filter->price) && $filter->price->id != $item->product_price->id) {
                        continue;
                    }

                    $all_options = true;
                    foreach ($filter->options as $option) {
                        if (!isset($option)) {
                            continue;
                        }
                        $found = false;
                        foreach ($item->options as $_option) {
                            if ($_option->id == $option->id) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $all_options = false;
                            break;
                        }
                    }

                    if (!$all_options) {
                        continue;
                    }

                    $count += $item->amount;
                }
                $sheet->setCellValue($letter . ($index + $startIndex), $count);
            }
        }

        // Add totals
        $index++;
        $letter = 'A';
        $sheet->setCellValue($letter . ($index + $startIndex), 'Totaal');
        $sheet->getStyle('A' . ($index + $startIndex) . ':' . $sheet->getHighestColumn() . ($index + $startIndex))->getFont()->setBold(true);

        foreach ($filters as $filter) {
            $letter++;
            $sheet->setCellValue($letter . ($index + $startIndex), '=SUM(' . $letter . $startIndex . ':' . $letter . ($startIndex + $index - 1) . ')');
        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $this->order_sheet->name . '.xlsx"');
        $writer->save('php://output');

        exit;

        return Template::render('admin/webshop/order-sheet-orders', array(
            'sheet' => $this->order_sheet,
            'orders' => $orders,
        ));
    }
}
