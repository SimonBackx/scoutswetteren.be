<?php
namespace Pirate\Sails\SintJan\Pages\Info;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Leden\Models\Inschrijving;
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
        $takken = Environment::getSetting('scouts.takken');

        $verdeling_string = array();
        foreach ($takken as $taknaam => $tak) {
            $min = $scoutsjaar - $tak['age_end'];
            $max = $scoutsjaar - $tak['age_start'];
            if ($min == $max) {
                $verdeling_string[$taknaam] = $min;
            } else {
                $verdeling_string[$taknaam] = $min . ' - ' . $max;
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
