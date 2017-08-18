<?php
namespace Pirate\Sail\Leiding\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

class Leidingsverdeling extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $success = false;
        $errors = array();

        $leiding_zichtbaar = Leiding::isLeidingZichtbaar();
        $leidingsverdeling = Leiding::getLeidingsverdeling();

        if (isset($_POST['submit'])) {
            if (!isset($_POST['zichtbaar'])) {
                $success = Leiding::disableLeidingsverdeling();
            } else {
                if (isset($_POST['date'], $_POST['time'])) {
                    $success = Leiding::setLeidingsverdeling($errors, $_POST['date'], $_POST['time']);
                }
            }

        }

        return Template::render('leiding/admin/leidingsverdeling', array(
            'leidingsverdeling' => $leidingsverdeling,
            'leiding_zichtbaar' => $leiding_zichtbaar,
            'errors' => $errors,
            'success' => $success
        ));
    }
}