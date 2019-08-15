<?php

// All configuration for scoutswetteren
$private = include __DIR__ . '/config.private.php';
$config = [
    'domain' => 'scoutssintjan.be',
    'name' => 'Scouts Sint-Jan Wetteren',
    'force_www' => true,
    'theme' => 'sint-jan',
    'disable_sails' => ['info'],

    'mysql' => [
        // no need to put in private. only localhost connections allowed
        'database' => 'sint-jan',
        'username' => 'root',
        'password' => 'root',
    ],
    'address' => [
        "street" => 'Groenstraat',
        "number" => '33',
        "postalcode" => '9230',
        "city" => 'Wetteren',
        'region' => 'Oost-Vlaanderen',
        "country" => 'BE',
    ],

    'router' => [
        'redirects' => [],
    ],

    'sendgrid' => [
        // key defined in private
    ],
    'sentry' => [
        // url defined in private config
    ],

    'mail' => [
        'name' => null,
        'mail' => 'website@scoutssintjan.be',
    ],
    'development_mail' => [
        'name' => null,
        'mail' => 'website@scoutssintjan.be',
    ],

    'scouts' => [
        'inschrijvings_start_maand' => 9 - 1,
        'inschrijvings_einde_maand' => 7 - 1,
        'inschrijvings_halfjaar_maand' => 3,

        'takken' => [
            "akabe" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 6,
                'age_end' => 20,
                'gender' => null, // M / V
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => false,
                'require_mobile' => false,
            ],

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

            "kabouters" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 8,
                'age_end' => 10,
                'gender' => "V", // M / V
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => false,
            ],

            "welpen" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 8,
                'age_end' => 10,
                'gender' => "M", // M / V
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => false,
            ],

            "jonggidsen" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 11,
                'age_end' => 13,
                'gender' => 'V', // M / V
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => false,
            ],

            "jongverkenners" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 11,
                'age_end' => 13,
                'gender' => 'M', // M / V
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => false,
            ],

            "gidsen" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 14,
                'age_end' => 16,
                'gender' => 'V',
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => true,
            ],

            "verkenners" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 14,
                'age_end' => 16,
                'gender' => 'M',
                'lidgeld' => 40,
                'lidgeld_halfjaar' => 20,
                'auto_assign' => true,
                'require_mobile' => true,
            ],

            "jin" => [
                /// Age is expressed as in the age everyone is at the end of december.
                'age_start' => 17,
                'age_end' => 17, // Special case, should be 17
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
