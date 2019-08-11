<?php
namespace Pirate\Sails\SintJan\Pages\Info;

use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Algemeen extends Page
{
    public function __construct()
    {
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $scoutsjaar = Inschrijving::getScoutsjaar();
        $takkenverdeling = Lid::getTakkenVerdeling($scoutsjaar, 'M');
        $jaar_verdeling = array();
        foreach ($takkenverdeling as $jaar => $tak) {
            if (!isset($jaar_verdeling[$tak])) {
                $jaar_verdeling[$tak] = array();
            }
            $jaar_verdeling[$tak][] = $jaar;
        }

        $verdeling_string = array();
        foreach ($jaar_verdeling as $tak => $jaren) {
            $min = min($jaren);
            $max = max($jaren);
            if ($min == $max) {
                $verdeling_string[$tak] = $min;
            } else {
                $verdeling_string[$tak] = $min . ' - ' . $max;
            }
        }

        return Template::render('pages/info/algemeen', array(
            'takkenverdeling' => $verdeling_string,
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/'),
            ),
        ));
    }
}
