<?php
namespace Pirate\Sails\Leden\Admin;

use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Overview extends Page
{
    public $tak = '';
    public $jaar = null;

    public function __construct($tak = '', $jaar = null)
    {
        $this->tak = $tak;
        $this->jaar = $jaar;
    }
    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $user = Leiding::getUser();

        $tak = $this->tak;
        $takken = Inschrijving::getTakken();

        if (empty($tak) && !empty($user->tak)) {
            $tak = $user->tak;
        }

        $leden = Lid::getLedenForTak($tak, $this->jaar);

        $prev = Inschrijving::getScoutsjaar() - 1;
        if (isset($this->jaar)) {
            $prev = $this->jaar - 1;
        }

        return Template::render('admin/leden/overview', array(
            'leden' => $leden,
            'takken' => $takken,
            'jaar' => $this->jaar,
            'prev' => $prev,
            'tak' => $tak,
        ));
    }
}
