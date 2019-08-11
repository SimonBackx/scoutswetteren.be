<?php
namespace Pirate\Sails\Blog\Blocks;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Blog\Models\Article;

class Overview extends Block {

    function getArticlesRaw($page = 1) {
        $articles = Article::getArticles($page);

        $data = array();

        $max = 4;
        if ($page == 0) {
            $max = 150;
        }

        for ($i=0; $i < count($articles) && $i < $max; $i++) { 
            $article = $articles[$i];
            $data[] = array(
                'title' => $article->title,
                'date' => datetimeToDateString($article->published),
                'short' => snippetFromHtml($article->html),
                'url' => $article->getUrl(),
            );
        }
        return array(
                    'articles' => $data,
                    'page' => $page,
                    'has_more' => (count($articles) == $max+1)
                );
    }
    // Geeft volledige block
    function getArticles($page = 1) {
        return Template::render('pages/blog/articles', $this->getArticlesRaw($page));
    }

    // Geeft volledige block
    function getContent() {
        return Template::render('pages/blog/overview', $this->getArticlesRaw(1));
    }

}