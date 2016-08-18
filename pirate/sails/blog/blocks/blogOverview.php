<?php
namespace Pirate\Sail\Blog\Blocks;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Model;
use Pirate\Model\Article;

class BlogOverview extends Block {

    // Geeft volledige block
    function getContent() {

         // Evenementen ophalen
        Model::loadModel('blog', 'article');
        $articles = Article::getArticles(1);

        $data = array();

        for ($i=0; $i < count($articles) && $i < 4; $i++) { 
            $article = $articles[$i];
            $data[] = array(
                'title' => $article->title,
                'date' => datetimeToDateString($article->published),
                'short' => snippetFromHtml($article->html),
                'url' => '/blog/'.datetimeToUrl($article->published).'/'.$article->slug,
            );
        }
        


        return Template::render('blog/blog-overview', 
                array(
                    'articles' => $data,
                    'has_more' => (count($articles) == 5)
                )
            );
    }

}