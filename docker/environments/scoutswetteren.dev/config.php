<?php

// All configuration for scoutswetteren
$private = include __DIR__ . '/config.private.php';
$config = [
    'domain' => 'scoutswetteren.be',
    'name' => 'Scouts Prins Boudewijn Wetteren',
    'force_www' => true,
    'theme' => 'prins-boudewijn',
    'disable_sails' => ['sint-jan'],

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

    'space' => [
        'name' => 'scouts',
        'region' => 'ams3',
        'server' => 'digitaloceanspaces.com',
        // key + secret in private config
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
                'description' => 'De Kapoenen is de tak voor de allerkleinsten. Het leven van een kapoen (6-8) is er één vol spel, fantasie, creativiteit en expressie. Sommigen gaan voor de eerste keer naar de scouts, voor de eerste keer op weekend, voor de eerste keer op kamp. Zoveel nieuwe dingen! Wat doen we bij de kapoenen? We spelen, we ravotten, we maken ons vuil, we kliederen en kladderen, we zingen, we klimmen in de bomen... Een heleboel dus! Speelkledij is een must!',
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
                'description' => 'Wie tussen 8 en 11 jaar is, kan meespelen bij de wouters. Zij voelen zich vaak al ervaren scouten, en dat zijn ze eigenlijk ook wel een beetje. Wouters worden zich steeds meer bewust van wat er rond hen gebeurt: op school, thuis en op de scouts. Ze krijgen ruimte en kansen om dingen uit te proberen en van elkaar te leren. Ze verleggen hun grenzen, halen kattenkwaad uit en kunnen vooral onbezorgd spelen.',
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
                'optional_mobile' => true,
                'description' => 'De jonggivers, een geval apart. Niet oud, maar ook niet zo jong meer. In ieder geval in volle ontwikkeling. Ze mogen al alleen op pad, leren sjorren, kaartlezen en gaan op tentenkamp, waar ze zelfs hun eigen potje leren koken! Jonggivers krijgen een waaier van mogelijkheden om mee te beslissen, zelf de handen uit de mouwen te steken en allerlei vaardigheden onder de knie te krijgen. Een tak boordevol nieuwe dingen!',
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
                'description' => 'Givers trekken samen op, hun vrienden zijn heilig. Ze hechten heel veel belang aan de groepssfeer. Van hun leiding krijgen ze alle mogelijkheden, ervaringen, spanning en enthousiasme die ze alleen binnen scouting kunnen vinden. Maar givers hebben ook hun eigen willetje. Ze krijgen dan ook elke keer dat ietsje meer. Dat ietsje meer participatie, dat ietsje meer zelfstandigheid, dat ietsje meer uitdaging. Als je hier zit, ben je al een echte!',
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
                'description' => 'Jin, Jij en Ik een Noodzaak. Als jin sta je op een grens: die tussen lid en leiding. In hun eigen \'jonge\' stijl werken ze gekke activiteiten, projecten en zelfs hun kamp uit. Al doende leren ze samen te werken en verantwoordelijkheid op te nemen. Hieruit groeit engagement voor de groep en voor de samenleving. Het is een jaar waarin ze hun eigen zegje hebben, waar ze samen met hun medejin tot ideeën komen en ze samen uitwerken. De jin gaat op buitenlands kamp. Ze organiseren financiële activiteiten om dat te kunnen bekostigen. Ze mogen zelf hun kamp invullen en vorm geven. Jin, gun ze hun apenjaar!',
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
