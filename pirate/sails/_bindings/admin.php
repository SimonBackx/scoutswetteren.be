<?php

// Lijst met modules die de admin.php hebben met een AdminRouter
$admin_routes = array('leiding', 'maandplanning', 'leden', 'blog', 'verhuur', 'photos', 'dependencies', 'sponsors');
$admin_pages = array(
    '' => array(
        array('priority' => true, 'name' => 'Mijn gegevens', 'url' => ''),
        array('name' => 'Maandplanning', 'url' => 'maandplanning'),
        array('name' => 'Foto\'s', 'url' => 'photos')
       //array('name' => 'Blog', 'url' => 'blog'),
        //array('name' => 'Foto\'s', 'url' => 'fotos'),
    ),
    'leiding' => array(
        array('priority' => true, 'name' => 'Inschrijvingen', 'url' => 'inschrijvingen')
    ),
    'verhuur' => array(
         array('priority' => true, 'name' => 'Verhuur', 'url' => 'verhuur')
    ),
    'oudercomite' => array(
         array('priority' => true, 'name' => 'Verhuur', 'url' => 'verhuur')
    ),
    'groepsleiding' => array(
        array('name' => 'Leiding', 'url' => 'leiding')
    ),
    'financieel' => array(
        array('priority' => true, 'name' => 'Afrekeningen', 'url' => 'afrekeningen')
    )
);