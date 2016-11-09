<?php
namespace Pirate\Sail\Homepage\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Homepage extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Block ophalen van maandplanning sail
        
        $maandplanning = Block::getBlock('Maandplanning', 'Kalender')->getContent();
        $blog = Block::getBlock('Blog', 'Overview')->getContent();

        $sponsors = array(
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
        );

/*yves-baeyens.png
wynants.png
vsf-verzekeringen.png
vastgoed-de-vuyst.png
vandeveldehout.png
oosterlinck.png
riffood.png
mercedes.png
kbc.png
haldis.png
enkadree.png
donners.png
demangerie.png
debeurs.png
de-keukeleire.png
cafe-tgesproken-dagblad.png
cafe-reynaert.png
brickplanet.png
bracke.png
adp.png
*/
        shuffle($sponsors);
        $sponsors = array_slice($sponsors, 0, 6);

        return Template::render('homepage', array(
            'menu' => array('transparent' => true),
            'maandplanning' => $maandplanning,
            'blog' => $blog,
            'sponsors' => $sponsors,
            'slideshows' => array(
                array(
                    'title' => 'Wafelbak wordt uitgesteld',
                    'text' => 'Door omstandigheden wordt de wafelbak uitgesteld naar het 2e semester. De werkvergadering voor de ouders en leiding gaat wel nog steeds door op 12 november. Zondag 13 november is het dus gewoon scouts.'//,
                    /*'button' => array('text' => 'Meer lezen', 'url' => '/blog/2016/10/15/wafelverkoop-12-november-2016'),
                    'extra_button' => array('text' => 'Bestelformulier', 'url' => '/files/wafelverkoop2016.pdf')*/
                ),
                array(
                    'title' => 'Een nieuwe huisstijl!',
                    'text' => 'Met onze nieuwe website en huisstijl kan je vanaf nu ook online inschrijven. En de maandplanning vind je nu altijd terug op de startpagina',
                    'button' => array('text' => 'Meer lezen', 'url' => '/blog/2016/09/09/nieuwe-huisstijl-en-online-inschrijven')
                ),
                array(
                    'title' => 'Startdag 2016',
                    'text' => 'Zondag 11 september vliegen we er weer in vanaf 14 uur! En vrijdag 9 september om 19 uur kan je komen genieten van lekkere streekbieren of frisdranken op onze streekbieravond.'
                )
            ),
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}