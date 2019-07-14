<?php
namespace Pirate\Sail\Photos\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Album;

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