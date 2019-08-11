<?php
namespace Pirate\Sails\Blog\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Blog\Models\Article;

class Overview extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        /*$user = Leiding::getUser();

        $tak = '';
        if (!empty($user->tak)) {
            $tak = $user->tak;
        } 

        // TODO: aanpassen zodat evenementen uit de huidige week, VOOR vandaag ook worden meegegeven
        $leden = Lid::getLedenForTak($tak);

        return Template::render('admin/leden/overview', array(
            'leden' => $leden,
            'tak' => $tak
        ));*/
        return 'WIP';
    }
}