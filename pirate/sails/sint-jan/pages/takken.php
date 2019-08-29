<?php
namespace Pirate\Sails\SintJan\Pages;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Maandplanning\Models\Event;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Takken extends Page
{
    public $tak;

    public function __construct($tak)
    {
        $this->tak = $tak;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        // Al events of coming 4 months
        $events = Event::getEventsForTak($this->tak);
        $event_groups = [];

        foreach ($events as $event) {
            $event_groups[$event->startdate->format('Y-m')][] = $event;
        }

        ksort($event_groups);
        $event_groups = array_values($event_groups);

        return Template::render('pages/takken/tak', [
            'taknaam' => $this->tak,
            'description' => Inschrijving::isGeldigeTak($this->tak) ? Environment::getSetting('scouts.takken.' . $this->tak . '.description') : '',
            'takken' => Inschrijving::getTakken(),
            'leiding_verborgen' => !Leiding::isLeidingZichtbaar(),
            'leiding' => Leiding::getLeiding(null, $this->tak),
            'events' => $event_groups,
        ]);
    }
}
