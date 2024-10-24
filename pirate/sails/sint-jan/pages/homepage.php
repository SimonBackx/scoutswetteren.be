<?php
namespace Pirate\Sails\SintJan\Pages;

use Pirate\Sails\Blog\Models\Article;
use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Maandplanning\Models\Event;
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

    public function getMaandplanning()
    {
        // Voor alle takken de eerstvolgende activieit vinden
        $takken = Environment::getSetting('scouts.takken');
        $first_activities = [];
        $save_the_date = [];

        // Komende 6 maand
        $events = Event::getEvents(date('Y-m-d'), date('Y-m-d', time() + 60 * 60 * 24 * 31 * 6));

        foreach ($events as $event) {
            if ($event->group == 'Alle takken') {
                foreach ($takken as $taknaam => $takinfo) {
                    if (!isset($first_activities[$taknaam])) {
                        $event_copy = clone $event;
                        $event_copy->group = ucfirst($taknaam);
                        $first_activities[$taknaam] = $event_copy;
                    }
                }

            } else {
                if (array_key_exists(strtolower($event->group), $takken)) {
                    if (!isset($first_activities[strtolower($event->group)])) {
                        $first_activities[strtolower($event->group)] = $event;
                    }
                }
            }

            if ($event->isImportantActivity() && count($save_the_date) < 6) {
                $save_the_date[] = $event;
            }

        }

        return [
            'first_activities' => array_values($first_activities),
            'save_the_date' => $save_the_date,
        ];
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
        $albums = Album::getAlbums(null, 1, false, 1, true);
        $album_images = [];

        foreach ($albums as $album) {
            $images = Image::getImagesFromAlbum($album->id);
            shuffle($images);
            $album_images[] = [
                'album' => $album,
                'images' => $images,
                'formatted_date' => datetimeToDayMonth($album->date_taken),
            ];
        }
        return $album_images;
    }

    public function getContent()
    {
        return Template::render('pages/homepage/homepage', array(
            'menu' => array('transparent' => true),
            'album_images' => $this->getAlbums(),
            'blog' => $this->getBlog(),
            'maandplanning' => $this->getMaandplanning(),
        ));
    }
}
