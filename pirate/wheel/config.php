<?php

$config = array(
    'days' => array('maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'),
    'months' => array('januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december')
);

function datetimeToDateString($datetime) {
    global $config;
    return $datetime->format('d').' '.$config['months'][$datetime->format('n')-1];
}
function datetimeToWeekday($datetime) {
    global $config;
    return $config['days'][$datetime->format('N')-1];
}