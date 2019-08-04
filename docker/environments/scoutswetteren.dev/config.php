<?php

// All configuration for scoutswetteren
$private = include __DIR__ . '/config.private.php';
$config = [
    'domain' => 'scoutswetteren.be',
    'name' => 'Scouts Prins Boudewijn Wetteren',
    'force_www' => true,
    'theme' => 'prins-boudewijn',
    'mysql' => [
        // no need to put in private. only localhost connections allowed
        'database' => 'scouts',
        'username' => 'root',
        'password' => 'root',
    ],
    'address' => [
        "street" => 'Groene wegel',
        "number" => '2',
        "postalcode" => '9230',
        "city" => 'Wetteren',
        'region' => 'Oost-Vlaanderen',
        "country" => 'BE',
    ],

    'router' => [
        'redirects' => [
            'winterfeest' => '/inschrijvingen/1/inschrijven-voor-winterfeest',
        ],
    ],

    'sendgrid' => [
        // key defined in private
    ],
    'sentry' => [
        // url defined in private config
    ],

    'mail' => [
        'name' => null,
        'mail' => 'website@scoutswetteren.be',
    ],
    'development_mail' => [
        'name' => null,
        'mail' => 'website@scoutswetteren.be',
    ],

    'scouts' => [
        'inschrijvings_start_maand' => 9,
        'inschrijvings_einde_maand' => 7,
        'inschrijvings_halfjaar_maand' => 3,

        'takken' => [
            "kapoenen" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 6,
                'age_end' => 7,
                'gender' => null, // M / V
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => false,
            ],
            "wouters" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 8,
                'age_end' => 10,
                'gender' => null, // M / V
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => false,
            ],
            "jonggivers" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 11,
                'age_end' => 13,
                'gender' => null, // M / V
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => false,
            ],
            "givers" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 14,
                'age_end' => 16,
                'gender' => null,
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => true,
            ],
            "jin" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 17,
                'age_end' => 18, // Special case, should be 17
                'gender' => null,
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true, // if auto assign is off, members need to moved manually. Renewed membership will be automatically in the same tak.
                'require_mobile' => true,
            ],

            /*
        $scoutsjaar - 7 => 'kapoenen', $scoutsjaar - 6 => 'kapoenen',
        $scoutsjaar - 8 => 'wouters', $scoutsjaar - 9 => 'wouters', $scoutsjaar - 10 => 'wouters',
        $scoutsjaar - 11 => 'jonggivers', $scoutsjaar - 12 => 'jonggivers', $scoutsjaar - 13 => 'jonggivers',
        $scoutsjaar - 14 => 'givers', $scoutsjaar - 15 => 'givers', $scoutsjaar - 16 => 'givers',
        $scoutsjaar - 17 => 'jin', $scoutsjaar - 18 => 'jin',*/
        ],
    ],

    'contacts' => [
        'groepsleiding' => array(
            'name' => 'Groepsleiding',
            'mail' => 'groepsleiding@scoutswetteren.be',
        ),
        'kapoenen' => array(
            'name' => 'Kapoenleiding',
            'mail' => 'kapoenen@scoutswetteren.be',
        ),
        'wouters' => array(
            'name' => 'Wouterleiding',
            'mail' => 'wouters@scoutswetteren.be',
        ),
        'jonggivers' => array(
            'name' => 'Jonggiverleiding',
            'mail' => 'jonggivers@scoutswetteren.be',
        ),
        'givers' => array(
            'name' => 'Giverleiding',
            'mail' => 'givers@scoutswetteren.be',
        ),
        'jin' => array(
            'name' => 'Jinleiding',
            'mail' => 'jin@scoutswetteren.be',
        ),
        'kerstactiviteit' => array(
            'name' => 'Kerstactiviteit',
            'mail' => 'kerstactiviteit@scoutswetteren.be',
        ),
        'winterfeest' => [
            "name" => 'Winterfeest',
            'mail' => "winterfeest@scoutswetteren.be",
        ],
        'wafelbak' => array(
            'name' => 'Wafelbak',
            'mail' => 'wafels@scoutswetteren.be',
        ),
        'webmaster' => array(
            'name' => 'Webmaster',
            'mail' => 'website@scoutswetteren.be',
        ),
        'materiaal' => array(
            'name' => 'Materiaalmeesters',
            'mail' => 'materiaal@scoutswetteren.be',
        ),
        'verhuur' => array(
            'name' => 'Verhuur verantwoordelijke',
            'permission' => 'verhuur',
        ),
        'oudercomite' => array(
            'name' => 'Oudercomité',
            'permission' => 'contactpersoon_oudercomite',
        ),
    ],
];

return array_merge($config, $private);
