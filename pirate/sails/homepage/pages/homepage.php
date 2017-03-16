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
                    'title' => 'WI WA Wafelbak 2017!',
                    'text' => 'Op zondag 26 maart gaan we weer heerlijke wafeltjes verkopen! Plaats op voorhand uw bestelling + We zoeken nog naar behulpzame ouders die ons willen helpen bakken.',
                    'button' => array('text' => 'Meer info', 'url' => '/blog/2017/03/26/wafelbak-2017'),
                    'extra_button' => array('text' => 'Bestellen', 'url' => 'https://files.scoutswetteren.be/manueel/wafels-bestellen.pdf')
                ),

                array(
                    'title' => 'Winterfeest: 12 februari',
                    'text' => 'We nodigen iedereen uit voor ons jaarlijks eetfestijn met dit jaar als thema: "Prinsjes Got Talent". Inschrijven is verplicht.',
                    'button' => array('text' => 'Inschrijven + meer info', 'url' => '/blog/2017/01/09/winterfeest-2017')
                ),

                array(
                    'title' => 'Kerstactiviteit 16 december',
                    'text' => 'Zoals ieder jaar organiseren we dan onze fameuze fakkel- en sneukeltocht met na de tocht de mogelijkheid om iets te drinken op het scoutsterrein. Inschrijven verplicht.',
                    'button' => array('text' => 'Inschrijven + meer info', 'url' => '/blog/2016/11/27/kerstactiviteit-2016')
                ),
                array(
                    'title' => 'Gloednieuwe foto pagina',
                    'text' => 'We hebben de foto pagina afgewerkt. Daar vind je de recentste foto\'s van je lieve spruit.',
                    'button' => array('text' => 'Foto\'s bekijken', 'url' => '/fotos')
                ),
                array(
                    'title' => 'Wafelbak wordt uitgesteld',
                    'text' => 'Door omstandigheden wordt de wafelbak uitgesteld naar het 2e semester. De werkvergadering voor de ouders en leiding gaat wel nog steeds door op 12 november. Zondag 13 november is het dus gewoon scouts.'//,
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