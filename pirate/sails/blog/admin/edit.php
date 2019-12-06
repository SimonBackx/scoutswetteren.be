<?php
namespace Pirate\Sails\Blog\Admin;

use Pirate\Sails\Blog\Models\Article;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Edit extends Page
{
    public $article;
    public function __construct($article = null)
    {
        $this->article = $article;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        return Template::render('admin/blog/edit', array(
            'article' => $this->article,
        ));
    }
}
