<?php
namespace Pirate\Sails\Blog\Admin;

use Pirate\Sails\Blog\Models\Article;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Overview extends Page
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {

        $articles = Article::getArticles(1, 200);

        return Template::render('admin/blog/overview', array(
            'articles' => $articles,
        ));
    }
}
