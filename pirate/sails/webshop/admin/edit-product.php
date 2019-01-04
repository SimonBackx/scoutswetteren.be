<?php
namespace Pirate\Sail\Webshop\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Webshop\Product;

use Pirate\Classes\Validating\ValidationError;
use Pirate\Classes\Validating\ValidationErrors;
use Pirate\Classes\Validating\ValidationErrorBundle;

class EditProduct extends Page {
    private $product = null;

    function __construct($product = null) {
        $this->product = $product;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Geen geldig id = nieuw event toevoegen
        $new = !isset($this->product);
        $errors = array();
        $success = false;

        $data_product = array(
            'name' => '',
            'description' => '',
            'type' => '',
            'price_name' => '',
            // 'prices' => [],
            // 'optionsets' => [],
        );

        $data_price = array(
            'id' => '',
            'name' => '',
            'price' => '',
        );

        $data_option = [
            'id' => '',
            'name' => '',
            'price_change' => '',
        ];

       
        if (isset($this->product)) {
            $data_product['name'] = $this->product->name;
            $data_product['description'] = $this->product->description;
            $data_product['type'] = $this->product->type;
            $data_product['price_name'] = $this->product->price_name;
        } else {
           $this->product = new Product();
        }

        $allset = true;
        foreach ($data_product as $key => $value) {
            if (!isset($_POST[$key])) {
                $allset = false;
                break;
            }
            
            $data_product[$key] = $_POST[$key];
        }

        $data_product['prices'] = [];
        $data_product['optionsets'] = [];


        if (isset($this->product)) {

            foreach ($this->product->prices as $price) {
                $data_product['prices'][] = [
                    'id' => $price->id,
                    'name' => $price->name,
                    'price' => $price->getPrice(),
                ];
            } 

            // todo: optionsets
        }
        
        if (isset($_POST['prices']) && is_array($_POST['prices'])) {
            $data_product['prices'] = [];

            foreach ($_POST['prices'] as $post) {
                $data = $data_price; 
                foreach ($data_price as $key => $value) {
                    if (!isset($post[$key])) {
                        $allset = false;
                        break(2);
                    }
                    
                    $data[$key] = $post[$key];
                }

                $data_product['prices'][] = $data;
            }
        } else {
            $allset = false;
        }

        if (isset($_POST['optionsets']) && is_array($_POST['optionsets'])) {
            $data_product['optionsets'] = [];

            foreach ($_POST['optionsets'] as $post) {
                if (isset($post['id'], $post['name'], $post['options']) && is_array($post['options'])) {
                    $options = [];

                    foreach ($post['options'] as $post_option) {
                        $data = $data_option; 
                        foreach ($data_option as $key => $value) {
                            if (!isset($post_option[$key])) {
                                $allset = false;
                                break(2);
                            }
                            
                            $data[$key] = $post_option[$key];
                        }
        
                        $options[] = $data;
                    }

                    $data_product['optionsets'][] = [
                        'id' => $post['id'],
                        'name' => $post['name'],
                        'options' => $options,
                    ];

                } else {
                    $allset = false;
                }
               
            }
        }

        if (count($data_product['prices']) == 0) {
            $allset = false;
            $data_product['prices'][] = $data_price;
        }


        // Als alles geset is
        if ($allset) {
            // todo

            try {
                $this->product->setProperties($data_product);
                if (!$this->product->save()) {
                    throw new ValidationError("Opslaan mislukt");
                }
            } catch (ValidationErrorBundle $ex) {
                foreach ($ex->getErrors() as $error) {
                    $errors[] = $error->message;
                }
            }

        } else {
            // Read from existing product

            // Add default prices placeholders if not set
        }

        return Template::render('webshop/admin/edit-product', array(
            'new' => $new,
            'data' => $data_product,
            'errors' => $errors,
            'success' => $success,
            'types' => Product::$types,
        ));
    }
}