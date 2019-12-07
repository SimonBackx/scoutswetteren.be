<?php

namespace Pirate\Sails\Blog;

use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\Route;

class BlogApiRouter extends Route
{
    public function doMatch($url, $parts)
    {
        if ($result = $this->match($parts, '/get-page/@page', ['page' => 'integer'])) {
            $this->setPage(new Api\GetPage(max(1, $result->params->page)));
            return true;
        }

        if ($this->match($parts, '/search')) {
            $this->setPage(new Api\Search($_GET['q']));
            return true;
        }

        if (!Leiding::hasPermission('redacteur')) {
            return false;
        }

        if ($this->match($parts, '/upload-file')) {
            $this->setPage(new Api\UploadFile());
            return true;
        }

        return false;
    }
}
