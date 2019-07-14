<?php
namespace Pirate\Sail\Homepage\Admin;

use Pirate\Classes\Validating\ValidationErrorBundle;
use Pirate\Model\Homepage\Slideshow;
use Pirate\Page\Page;
use Pirate\Template\Template;

class EditSlideshow extends Page
{
    private $slideshow = null;

    public function __construct($slideshow = null)
    {
        $this->slideshow = $slideshow;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        // Geen geldig id = nieuwe slideshow toevoegen
        $new = !isset($this->slideshow);
        $errors = array();
        $success = false;

        if (!isset($this->slideshow)) {
            $this->slideshow = new Slideshow();
        }

        $data = $this->slideshow->getData();

        if (isset($_POST["submit"])) {
            $data = $_POST;

            try {
                $this->slideshow->setProperties($_POST);
                $this->slideshow->save();
                header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/slideshows");
                $success = true;
            } catch (ValidationErrorBundle $bundle) {
                foreach ($bundle->getErrors() as $error) {
                    $errors[] = $error->message;
                }
            }
        }

        return Template::render('admin/slideshows/edit', array(
            'new' => $new,
            'data' => $data,
            'slideshow' => $this->slideshow,
            'errors' => $errors,
            'success' => $success,
        ));
    }
}
