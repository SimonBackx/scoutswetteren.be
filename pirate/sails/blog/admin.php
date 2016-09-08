<?php
namespace Pirate\Sail\Blog;
use Pirate\Page\Page;
use Pirate\Route\Route;

class BlogAdminRouter extends Route {
    private $id;

    function doMatch($url, $parts) {
        if (isset($parts[0]) && $parts[0] == 'blog') {
            if (count($parts) == 1) {
                return true;
            } elseif ($parts[1] == 'article' && count($parts) == 3) {
                if (!is_numeric($parts[2])) {
                    return false;
                }
                $this->id = intval($parts[2]);
                return true;
            }
        }

        return false;
    }

    function getPage($url, $parts) {
        if (count($parts) == 1) {
            require(__DIR__.'/admin/overview.php');
            return new Admin\Overview();
        }

        require(__DIR__.'/admin/lid.php');
        return new Admin\Article($this->id);
    }
}