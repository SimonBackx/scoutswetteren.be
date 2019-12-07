<?php
namespace Pirate\Sails\Blog;

use Pirate\Sails\Blog\Models\Article;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\AdminRoute;

class BlogAdminRouter extends AdminRoute
{
    private $id;

    public static function getAvailablePages()
    {
        return [
            'redacteur' => [
                array('priority' => 200, 'name' => 'Artikels', 'url' => 'articles'),
            ],
        ];
    }

    public function doMatch($url, $parts)
    {
        if (!Leiding::hasPermission('redacteur')) {
            return false;
        }

        if ($result = $this->match($parts, '/articles/new')) {
            $this->setPage(new Admin\Edit());
            return true;
        }

        if ($result = $this->match($parts, '/articles/@id', ['id' => 'integer'])) {
            $article = Article::get($result->params->id);

            if (empty($article)) {
                return false;
            }
            $this->setPage(new Admin\Edit($article));
            return true;
        }

        if ($result = $this->match($parts, '/articles/delete/@id', ['id' => 'integer'])) {
            $article = Article::get($result->params->id);

            if (empty($article)) {
                return false;
            }
            $this->setPage(new Admin\Delete($article));
            return true;
        }

        if ($result = $this->match($parts, '/articles', [])) {
            $this->setPage(new Admin\Overview());
            return true;
        }

        return false;
    }
}
