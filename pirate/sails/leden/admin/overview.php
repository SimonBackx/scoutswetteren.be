<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class Overview extends Page {
    public $tak = '';
    public $jaar = null;

    function __construct($tak = '', $jaar = null) {
        $this->tak = $tak;
        $this->jaar = $jaar;
    }
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $user = Leiding::getUser();

        $tak = $this->tak;
        $takken = Inschrijving::$takken;

        if (empty($tak) && !empty($user->tak)) {
            $tak = $user->tak;
        }

        $leden = Lid::getLedenForTak($tak, $this->jaar);

        $prev = Inschrijving::getScoutsjaar() - 1;
        if (isset($this->jaar)) {
            $prev = $this->jaar - 1;
        }

        return Template::render('leden/admin/overview', array(
            'leden' => $leden,
            'takken' => $takken,
            'jaar' => $this->jaar,
            'prev' => $prev,
            'tak' => $tak
        ));
    }
}