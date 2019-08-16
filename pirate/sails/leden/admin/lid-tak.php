<?php
namespace Pirate\Sails\Leden\Admin;

use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class LidTak extends Page
{
    private $lid;

    public function __construct(Lid $lid)
    {
        $this->lid = $lid;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $success = false;

        if (!$this->lid->isIngeschreven()) {
            return 'Fout';
        }

        if (isset($_POST['tak'])) {
            // Echt verwijderen en doorverwijzen
            $tak = $_POST['tak'];
            if (Inschrijving::isGeldigeTak($tak)) {
                $this->lid->inschrijving->tak = $tak;
                $success = $this->lid->inschrijving->save();
                header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/inschrijvingen/lid/" . $this->lid->id);
            }
        }

        return Template::render('admin/leden/lid-tak', array(
            'lid' => $this->lid,
            'takken' => Inschrijving::getTakken(),
            'success' => $success,
        ));
    }
}
