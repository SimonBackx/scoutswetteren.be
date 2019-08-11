<?php
namespace Pirate\Sails\Sponsors\Admin;

use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Sponsors\Models\Sponsor;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Edit extends Page
{
    private $sponsor = null;

    public function __construct($sponsor = null)
    {
        $this->sponsor = $sponsor;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        // Geen geldig id = nieuwe sponsor toevoegen
        $new = !isset($this->sponsor);
        $errors = array();
        $success = false;

        if (!isset($this->sponsor)) {
            $this->sponsor = new Sponsor();
        }

        $data = array(
            'name' => $this->sponsor->name,
            'url' => $this->sponsor->url,
        );

        $allset = true;
        foreach ($data as $key => $value) {
            if (!isset($_POST[$key])) {
                $allset = false;
                break;
            }

            $data[$key] = $_POST[$key];
        }

        if ($allset) {
            $this->sponsor->name = $data['name'];
            $this->sponsor->url = $data['url'];

            if (count($errors) == 0) {
                $form_name = "logo";
                if (File::isFileSelected($form_name)) {
                    $img = new Image();
                    if ($img->upload($form_name, array(array('height' => 160 * 2)), $errors, null, true)) {
                        $this->sponsor->image = $img;
                    }
                }
            }

            // Save
            if (count($errors) == 0) {
                if (!isset($this->sponsor->image)) {
                    $errors[] = 'Geen logo geselecteerd';
                } else {
                    $success = $this->sponsor->save();
                    if (!$success) {
                        $errors[] = 'Opslaan mislukt';
                    }
                }
            }
        }

        return Template::render('admin/sponsors/edit', array(
            'new' => $new,
            'data' => $data,
            'sponsor' => $this->sponsor,
            'errors' => $errors,
            'success' => $success,
        ));
    }
}
