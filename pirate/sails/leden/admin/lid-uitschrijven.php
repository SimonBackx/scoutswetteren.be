<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Inschrijving;

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