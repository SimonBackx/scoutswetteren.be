<?php
namespace Pirate\Sail\Verhuur\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\File;


class Materiaal extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // check for upload here

        $errors = array();
        $uploading = false;
        $succes = false;
        $location = "verhuur/";
        $file_name = "materiaal-prijslijst";
        $extension = "pdf";

        $form_name = "file";
        if (File::isFileSelected($form_name )) {
            $uploading = true;

            $file = new File();
            $file->location = $location;

            if ($file->upload($form_name, $errors, array($extension), $file_name)) {
                $succes = true;
            }
        }

      
        return Template::render('admin/verhuur/materiaal', array(
            'uploading' => $uploading,
            'errors' => $errors,
            'succes' => $succes,
            'location' => "https://".str_replace('www.','files.',$_SERVER['SERVER_NAME'])."/".$location.$file_name.'.'.$extension
        ));
    }
}