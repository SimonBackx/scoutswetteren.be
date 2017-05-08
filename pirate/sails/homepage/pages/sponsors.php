<?php
namespace Pirate\Sail\Homepage\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Sponsors\Sponsor;

class Sponsors extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Block ophalen van maandplanning sail
        
        $maandplanning = Block::getBlock('Maandplanning', 'Kalender')->getContent();
        $blog = Block::getBlock('Blog', 'Overview')->getContent();

        $sponsors = Sponsor::getSponsors();
        $sponsors_data = array();

        foreach($sponsors as $sponsor ){
            $data = array('src' => $sponsor->image->getBiggestSource()->file->getPublicPath(), 'name' => $sponsor->name);
            if (strlen($sponsor->url) > 0) {
                $data['url'] = 'http://'.$sponsor->url;
            }
            $sponsors_data[] = $data;
        }
        /*$sponsors_data = array(
            array('src' => 'yves-baeyens.png'),
            array('src' => 'wynants.png'),
            array('src' => 'vsf-verzekeringen.png', 'url' => 'http://www.vsfbvba.be'),
            array('src' => 'vastgoed-de-vuyst.png'),
            array('src' => 'vandeveldehout.png', 'url' => 'http://www.vdvhout.be'),
            array('src' => 'oosterlinck.png', 'url' => 'https://www.wooninrichting-oosterlinck.be'),
            array('src' => 'riffood.png', 'url' => 'https://www.facebook.com/Pitta-Rif-424971590884892/'),
            array('src' => 'mercedes.png', 'url' => 'http://www.dendermonde.mercedes-benz.be'),
            array('src' => 'kbc.png', 'url' => 'https://www.kbc.be'),
            array('src' => 'haldis.png', 'url' => 'https://www.facebook.com/haldisfriet/'),
            array('src' => 'enkadree.png', 'url' => 'http://www.enkadree.be'),
            array('src' => 'donners.png', 'url' => 'http://www.verzekeringendonners.be'),
            array('src' => 'demangerie.png', 'url' => 'http://www.de-mangerie.be'),
            array('src' => 'debeurs.png', 'url' => 'https://www.facebook.com/Café-de-beurs-1484563611847719'),
            array('src' => 'de-keukeleire.png', 'url' => 'http://www.dekeukeleire.be'),
            array('src' => 'cafe-tgesproken-dagblad.png', 'url' => 'https://www.facebook.com/pages/T-Gesproken-Dagblad/215383741980111'),
            array('src' => 'cafe-reynaert.png', 'url' => 'https://www.facebook.com/CafeReynaert'),
            array('src' => 'brickplanet.png', 'url' => 'https://www.jolico.be'),
            array('src' => 'bracke.png', 'url' => 'http://www.patisseriebracke.be'),
            array('src' => 'adp.png', 'url' => 'http://www.handelsgids.be/wetteren/adp-baetens-nv/')
        );*/


        shuffle($sponsors);

        return Template::render('sponsors', array(
            'sponsors' => $sponsors_data,
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}