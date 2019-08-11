<?php
namespace Pirate\Sails\Photos\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Files\Models\Album;

class Delete extends Page {
    private $album = null;

    function __construct($album) {
        $this->album = $album;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $success = false;
        $fail = false;

        if (isset($_POST['confirm-delete'])) {
            // Echt verwijderen en doorverwijzen
            $success = $this->album->delete();
            if ($success) {
                header("Location: https://".$_SERVER['SERVER_NAME']."/admin/photos");
            } else {
                $fail = true;
            }
        }

        return Template::render('admin/photos/delete', array(
            'album' => $this->album,
            'failed' => $fail,
            'success' => $success
        ));
    }
}