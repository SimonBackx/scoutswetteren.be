<?php
namespace Pirate\Sails\SintJan\Pages;

use Pirate\Sails\Blog\Models\Article;
use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Files\Models\Image;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Homepage extends Page
{
    public function __construct()
    {
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getBlog()
    {
        $articles = Article::getArticles(1, 6);

        $data = array();

        foreach ($articles as $article) {
            $data[] = array(
                'title' => $article->title,
                'date' => datetimeToDateString($article->published),
                'html' => $article->html,
                'url' => $article->getUrl(),
            );
        }
        return $data;
    }

    public function getAlbums()
    {
        $albums = Album::getAlbums(null, 1, false, 4);
        $album_images = [];

        foreach ($albums as $album) {
            $album_images[] = [
                'album' => $album,
                'images' => Image::getImagesFromAlbum($album->id),
                'formatted_date' => datetimeToDayMonth($album->date_taken),
            ];
        }
    }

    public function getContent()
    {
        return Template::render('pages/homepage/homepage', array(
            'menu' => array('transparent' => true),
            'album_images' => $this->getAlbums(),
            'blog' => $this->getBlog(),
        ));
    }
}
