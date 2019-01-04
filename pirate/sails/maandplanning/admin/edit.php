<?php
namespace Pirate\Sail\Maandplanning\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Maandplanning\Event;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Webshop\BankAccount;
use Pirate\Model\Webshop\OrderSheet;

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

        return Template::render('maandplanning/admin/edit', array(
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