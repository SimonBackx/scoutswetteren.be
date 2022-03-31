<?php
namespace Pirate\Sails\Info\Pages;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding as LeidingModel;
use Pirate\Sails\Environment\Classes\Environment;

class Leiding extends Page
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $leiding = LeidingModel::getLeiding();
        $takken = Environment::getSetting('scouts.takken');
        $grouped_data = [];

        foreach ($takken as $tak => $data) {
            $filtered = [];
            foreach ($leiding as $lid) {
                if ($lid->tak == $tak) {
                    $filtered[] = $lid;
                }
            }
            $grouped_data[]= [
                'name' => $tak,
                'data' => $data,
                'leiding' => $filtered,
            ];
        }

        // todo: group by tak
        return Template::render('pages/info/leiding', [
            'leiding_verborgen' => !LeidingModel::isLeidingZichtbaar(),
            'takken' => $grouped_data,
        ]);
    }
}
