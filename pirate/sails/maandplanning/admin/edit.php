<?php
namespace Pirate\Sails\Maandplanning\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Maandplanning\Models\Event;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Webshop\Models\BankAccount;
use Pirate\Sails\Webshop\Models\OrderSheet;

class Edit extends Page {
    private $id = null;

    function __construct($id = null) {
        $this->id = $id;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Geen geldig id = nieuw event toevoegen
        $new = true;
        $errors = array();
        $success = false;
        $accounts = BankAccount::getAll();

        $data = array(
            'id' => '',
            'name' => '',
            'startdate' => '',
            'enddate' => '',
            'overnachting' => false,
            'location' => '',
            'endlocation' => '',
            'group' => '',
            'starttime' => '',
            'endtime' => '',

            'order_sheet' => false,
            'order_sheet_account' => null,
            'order_sheet_type' => null,
            'order_sheet_due_date' => '',
            'order_sheet_description' => '',
            'order_sheet_mail' => '',
            'order_sheet_phone' => '',
        );

        if (!empty(Leiding::getUser()->tak)) {
            $data['group'] = ucfirst(Leiding::getUser()->tak);
        }

        if (isset($_GET['date'])) {
            $data['startdate'] = $_GET['date'];

            // Uren invullen met de standaard waarden
            $data['starttime'] = Event::getDefaultStartHour();
            $data['endtime'] = Event::getDefaultEndHour();

            $data['location'] = Event::$defaultLocation;
        }

        if (isset($_GET['name'])) {
            $data['name'] = $_GET['name'];
        }

        if (!is_null($this->id)) {
            $event = Event::getEvent($this->id);
            if (!is_null($event)) {
                $new = false;

                $data = array(
                    'name' => $event->name,
                    'startdate' => $event->startdate->format('d-m-Y'),
                    'enddate' => $event->enddate->format('d-m-Y'),
                    'overnachting' => ($event->startdate->format('d-m-Y') != $event->enddate->format('d-m-Y')),
                    'location' => $event->location,
                    'endlocation' => $event->endlocation,
                    'group' => $event->group,
                    'starttime' => $event->startdate->format('H:i'),
                    'endtime' => $event->enddate->format('H:i'),

                    'order_sheet' => false,
                    'order_sheet_type' => null,
                    'order_sheet_account' => null,
                    'order_sheet_due_date' => '',
                    'order_sheet_description' => '',
                    'order_sheet_mail' => '',
                    'order_sheet_phone' => '',
                );

                $data['id'] = $event->id;

                if (is_null($event->location)) {
                    $data['location'] = Event::$defaultLocation;
                }
                if (is_null($event->endlocation)) {
                    $data['endlocation'] = Event::$defaultLocation;
                }

                if (isset($event->order_sheet)) {
                    $data['order_sheet'] = true;
                    $data['order_sheet_account'] = $event->order_sheet->bank_account->id;
                    $data['order_sheet_due_date'] = isset($event->order_sheet->due_date) ? $event->order_sheet->due_date->format('d-m-Y') : '';
                    $data['order_sheet_description'] = $event->order_sheet->description;
                    $data['order_sheet_type'] = $event->order_sheet->type;
                    $data['order_sheet_mail'] = $event->order_sheet->mail;
                    $data['order_sheet_phone'] = $event->order_sheet->phone;

                }

            } else {
                $event = new Event();
            }
        } else {
           $event = new Event();
        }

        $allset = true;
        foreach ($data as $key => $value) {
            if ($key == 'overnachting' || $key == 'id')
                continue;
            if ($key == 'order_sheet')
                continue;

            if (!isset($_POST[$key])) {
                if ($key == 'group')
                    continue;

                
                $allset = false;
                break;
            }
            
            $data[$key] = $_POST[$key];
        }

        // Als alles geset is
        if ($allset) {
            if (isset($_POST['overnachting'])) {
                $data['overnachting'] = true;
            }

            if (isset($_POST['order_sheet'])) {
                $data['order_sheet'] = true;
            } else {
                $data['order_sheet'] = false;
            }

            // Nu één voor één controleren
            $errors = $event->setProperties($data);

            if (count($errors) == 0) {
                if ($event->save()) {
                    $success = true;
                    header("Location: https://".$_SERVER['SERVER_NAME']."/admin/maandplanning");
                }
                else
                    $errors[] = 'Probleem bij opslaan';
            }
        }

        return Template::render('admin/maandplanning/edit', array(
            'new' => $new,
            'event' => $data,
            'errors' => $errors,
            'groups' => Event::$groups,
            'default_locatie' => Event::$defaultLocation,
            'default_start_hour' => Event::getDefaultStartHour(),
            'default_end_hour' => Event::getDefaultEndHour(),
            'accounts' => $accounts,
            'ordersheet_types' => OrderSheet::$types,

            'success' => $success
        ));
    }
}