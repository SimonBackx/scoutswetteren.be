<?php
namespace Pirate\Sails\Verhuur\Admin;

use Pirate\Sails\Files\Models\File;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Materiaal extends Page
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        // check for upload here

        $errors = array();
        $uploading = false;
        $succes = false;
        $location = "verhuur/";
        $file_name = "materiaal-prijslijst";
        $extension = "pdf";

        $form_name = "file";
        if (File::isFileSelected($form_name)) {
            $uploading = true;

            $file = new File();
            $file->location = $location;

            if ($file->upload($form_name, $errors, array($extension), $file_name, true)) {
                $succes = true;
            }
        }

        return Template::render('admin/verhuur/materiaal', array(
            'uploading' => $uploading,
            'errors' => $errors,
            'succes' => $succes,
            'location' => "https://" . str_replace('www.', 'files.', $_SERVER['SERVER_NAME']) . "/" . $location . $file_name . '.' . $extension,
        ));
    }
}
