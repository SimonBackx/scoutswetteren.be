<?php
namespace Pirate\Sails\Leden\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leden\Models\Inschrijving;

class LidUitschrijven extends Page {
    private $lid;

    function __construct(Lid $lid) {
        $this->lid = $lid;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $success = false;

        if (!$this->lid->isIngeschreven()) {
            return 'Fout';
        }

        if (isset($_POST['confirm-delete'])) {
            // Echt verwijderen en doorverwijzen
            $success = $this->lid->inschrijving->uitschrijven();
            header("Location: https://".$_SERVER['SERVER_NAME']."/admin/inschrijvingen/lid/".$this->lid->id);
        }

        return Template::render('admin/leden/lid-uitschrijven', array(
            'lid' => $this->lid,
            'success' => $success
        ));
    }
}