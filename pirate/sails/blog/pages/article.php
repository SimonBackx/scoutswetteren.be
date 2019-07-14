<?php
namespace Pirate\Sail\Blog\Pages;
use Pirate\Page\Page;
use Pirate\Template\Template;

class Article extends Page {
    private $article;

    function __construct($article) {
        $this->article = $article;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('pages/blog/article', array(
            'title' => $this->article->title,
            
            'article' => array(
                'title' => $this->article->title,
                'date' => datetimeToDateString($this->article->published),
                'html' => $this->article->html
            ),
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}