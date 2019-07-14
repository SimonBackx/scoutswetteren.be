<?php
namespace Pirate\Sail\Blog\Pages;
use Pirate\Page\Page;
use Pirate\Template\Template;
use Pirate\Block\Block;

class Blog extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {

        $archief = Block::getBlock('Blog', 'Overview')->getArticles(0);

        return Template::render('pages/blog/blog', array(
            'title' => 'Blog archief',
            
            'content' => $archief,
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}