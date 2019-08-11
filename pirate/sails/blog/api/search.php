<?php
namespace Pirate\Sails\Blog\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;
use Pirate\Sails\Blog\Models\Article;

class Search extends Page {
    private $needle;

    function __construct($needle) {
        $this->needle = $needle;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $articles = Article::searchArticles($this->needle);

        $data = array('results' => array());

        foreach ($articles as $article) {
            $time_str = datetimeToDateString($article->published);

            $data['results'][] = array(
                'title' => $article->title,
                'date' => $time_str,
                'url' => $article->getUrl()
            );
        }

        return Template::render('pages/blog/search', $data );
    }
}