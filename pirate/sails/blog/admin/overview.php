<?php
namespace Pirate\Sail\Blog\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Blog\Article;

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

        return Template::render('leden/admin/overview', array(
            'leden' => $leden,
            'tak' => $tak
        ));*/
        return 'WIP';
    }
}