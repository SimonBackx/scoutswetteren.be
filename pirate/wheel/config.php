<?php

$config = array(
    'days' => array('maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'),
    'months' => array('januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december')
);

function datetimeToDateString($datetime) {
    global $config;
    $jaar = $datetime->format('Y') ;
    $now = new DateTime();
    if ($jaar ==  date("Y") && $now <= $datetime) {
        $jaar = '';
    } else {
        $jaar = ' '.$jaar;
    }
    return $datetime->format('j').' '.$config['months'][$datetime->format('n')-1].$jaar;
}
function datetimeToWeekday($datetime) {
    global $config;
    return $config['days'][$datetime->format('N')-1];
}