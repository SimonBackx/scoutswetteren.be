<?php
namespace Pirate\Sails\Leiding\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Settings\Models\Setting;

class Leidingsverdeling extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $success = false;
        $errors = array();

        $leiding_zichtbaar = Leiding::isLeidingZichtbaar();
        $leidingsverdeling = Leiding::getLeidingsverdeling();

        $groepsleiding_gsm_zichtbaar = Setting::getSetting('groepsleiding_gsm_zichtbaar', false);

        if (isset($_POST['submit'])) {
            if (!isset($_POST['zichtbaar'])) {
                $success = Leiding::disableLeidingsverdeling();
            } else {
                if (isset($_POST['date'], $_POST['time'])) {
                    $success = Leiding::setLeidingsverdeling($errors, $_POST['date'], $_POST['time']);
                }
            }

            if (isset($_POST['groepsleiding_gsm_zichtbaar'])) {
                $groepsleiding_gsm_zichtbaar->value = true;
            } else {
                $groepsleiding_gsm_zichtbaar->value = false;
            }
            if ($success) {
                $success = $groepsleiding_gsm_zichtbaar->save();
            }

        }

        return Template::render('admin/leiding/leidingsverdeling', array(
            'leidingsverdeling' => $leidingsverdeling,
            'leiding_zichtbaar' => $leiding_zichtbaar,
            'groepsleiding_gsm_zichtbaar' => $groepsleiding_gsm_zichtbaar->value,
            'errors' => $errors,
            'success' => $success
        ));
    }
}