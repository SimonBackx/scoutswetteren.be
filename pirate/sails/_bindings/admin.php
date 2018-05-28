<?php

// Lijst met modules die de admin.php hebben met een AdminRouter
$admin_routes = array('leiding', 'maandplanning', 'leden', 'blog', 'verhuur', 'photos', 'dependencies', 'sponsors', 'groepsadmin');
$admin_pages = array(
    '' => array(
        array('priority' => true, 'name' => 'Ik', 'url' => ''),
        array('name' => 'Maandplanning', 'url' => 'maandplanning'),
        array('name' => 'Foto\'s', 'url' => 'photos'),
        array('name' => 'Verhuur', 'url' => 'verhuur')
       //array('name' => 'Blog', 'url' => 'blog'),
        //array('name' => 'Foto\'s', 'url' => 'fotos'),
    ),
    'leiding' => array(
        array('priority' => true, 'name' => 'Leden', 'url' => 'inschrijvingen')
    ),
    'verhuur' => array(
         array('priority' => true, 'name' => 'Verhuur', 'url' => 'verhuur')
    ),
    'oudercomite' => array(
         array('priority' => true, 'name' => 'Verhuur', 'url' => 'verhuur'),
         array('name' => 'Sponsors', 'url' => 'sponsors')
    ),
    'groepsleiding' => array(
        array('name' => 'Leiding', 'url' => 'leiding'),
        array('name' => 'Verhuur', 'url' => 'verhuur'),
        array('name' => 'Sponsors', 'url' => 'sponsors')
    ),
    'financieel' => array(
        array('priority' => true, 'name' => 'Rekeningen', 'url' => 'afrekeningen')
    ),
    'materiaalmeester' => array(
        array('priority' => false, 'name' => 'Materiaal', 'url' => 'materiaal')
    ),
);