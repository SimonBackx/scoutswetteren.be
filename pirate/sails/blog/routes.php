<?php
namespace Pirate\Sails\Blog;
use Pirate\Wheel\Page;
use Pirate\Wheel\Route;
use  Pirate\Wheel\Model;
use  Pirate\Sails\Blog\Models\Article;

class BlogRouter extends Route {
    private $article;

    function doMatch($url, $parts) {
        if (count($parts) == 5) {
            if (preg_match('/^blog\/\d{4}\/\d{2}\/\d{2}\//', $url)) {
                // Controleren of dit artikel bestaat
                $date = $parts[1].'-'.$parts[2].'-'.$parts[3];
                $slug = $parts[4];

                $this->article = Article::getArticle($date, $slug);

                if (!is_null($this->article)) {
                    return true;
                }
            }
        } else {
            if (count($parts) == 1 && $parts[0] == 'blog') {
                return true;
            }
        }
        return false;
    }

    function getPage($url, $parts) {
        if (count($parts) == 1) {
            require(__DIR__.'/pages/blog.php');
            return new Pages\Blog();
        }

        require(__DIR__.'/pages/article.php');
        return new Pages\Article($this->article);
    }
}