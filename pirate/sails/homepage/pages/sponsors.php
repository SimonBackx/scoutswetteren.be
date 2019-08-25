<?php
namespace Pirate\Sails\Homepage\Pages;

use Pirate\Sails\Sponsors\Models\Sponsor;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Sponsors extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        // Block ophalen van maandplanning sail
        $sponsors = Sponsor::getSponsors();
        $sponsors_data = array();

        foreach ($sponsors as $sponsor) {
            $data = array('src' => $sponsor->image->getBiggestSource()->file->getPublicPath(), 'name' => $sponsor->name);
            if (strlen($sponsor->url) > 0) {
                if (strpos($sponsor->url, 'http://') !== 0 && strpos($sponsor->url, 'https://') !== 0) {
                    $data['url'] = 'http://' . $sponsor->url;
                } else {
                    $data['url'] = $sponsor->url;
                }
            }
            $sponsors_data[] = $data;
        }

        shuffle($sponsors_data);

        return Template::render('pages/homepage/sponsors', array(
            'sponsors' => $sponsors_data,
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/'),
            ),
        ));
    }
}
