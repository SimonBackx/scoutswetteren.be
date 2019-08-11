<?php
namespace Pirate\Sails\Homepage\Admin;

use Pirate\Sails\Homepage\Models\Slideshow;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class DeleteSlideshow extends Page
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
        $success = false;

        if (isset($_POST['confirm-delete'])) {
            // Echt verwijderen en doorverwijzen
            $success = $this->slideshow->delete();
            header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/slideshows");
        }

        return Template::render('admin/slideshows/delete', array(
            'slideshow' => $this->slideshow,
            'success' => $success,
        ));
    }
}
