<?php
namespace Pirate\Sails\Blog;
use Pirate\Wheel\Page;
use Pirate\Wheel\AdminRoute;

class BlogAdminRouter extends AdminRoute {
    private $id;

    static function getAvailablePages() {
        return [];
    }

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