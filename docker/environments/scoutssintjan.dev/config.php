<?php

// All configuration for scoutswetteren
$private = include __DIR__ . '/config.private.php';
$config = [
    'domain' => 'scoutssintjan.be',
    'name' => 'Scouts Sint-Jan Wetteren',
    'force_www' => true,
    'theme' => 'sint-jan',
    'disable_sails' => ['info', 'homepage'],

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

    'space' => [
        // key + secret in private config
    ],

    'router' => [
        'redirects' => [
            'lokaalverhuur' => '/verhuur',
            'eetfestijn' => '/inschrijvingen/1/inschrijven-voor-eetfestijn',
        ],
    ],

    'drive' => 'https://drive.google.com/drive/u/6/folders/0AIujUUpM-XqAUk9PVA',
    'stamhoofd' => true,
    'keep_old_leden' => false,

    'sendgrid' => [
        // key defined in private
    ],
    'sentry' => [
        // url defined in private config
    ],

    'mail' => [
        'name' => 'Scouts Sint-Jan Wetteren',
        'mail' => 'website@scoutssintjan.be',
    ],
    'development_mail' => [
        'name' => 'Simon Backx',
        'mail' => 'hi@simonbackx.com',
    ],

    'groepsadmin' => [
        // private
    ],

    'scouting_op_maat' => [
        'checkbox' => 'Bedankt, de groeps- en/of takleiding neemt contact met u op om samen te bekijken wat mogelijk is.',
    ],

    'bank' => [
        'iban' => 'BE18 7360 6504 8365',
        'bic' => 'KREDBEBB',
    ],

    'scouts' => [
        'override_url' => 'https://inschrijven.scoutssintjan.be',
        'inschrijvings_start_maand' => 8,

        'voorinschrijven_einde_maand' => 9,
        'voorinschrijven_einde_dag' => 13,

        'inschrijvings_einde_maand' => 6,
        'inschrijvings_halfjaar_maand' => 3,
        'lidgeld_verminderd' => 1 / 3,

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
                'optional_mobile' => true,
                'default_end_hour' => '17:30',
                'description' => 'Bij Akabe zit het scouting-DNA in hart en nieren! Elke persoon, ongeacht zijn beperkingen, moet de kansen en mogelijkheden krijgen om deel uit te maken van scouting.',
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
                'default_end_hour' => '17:00',
                'description' => 'De Kapoenen is de tak voor de allerkleinsten. Het leven van een kapoen (6-8) is er één vol spel, fantasie, creativiteit en expressie. Sommigen gaan voor de eerste keer naar de scouts, voor de eerste keer op weekend, voor de eerste keer op kamp. Zoveel nieuwe dingen! Wat doen we bij de kapoenen? We spelen, we ravotten, we maken ons vuil, we kliederen en kladderen, we zingen, we klimmen in de bomen... Een heleboel dus! Speelkledij is een must!',
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
                'default_end_hour' => '17:00',
                'description' => 'Wie tussen 8 en 11 jaar is, kan meespelen bij de kabouters of welpen (wouters). Zij voelen zich vaak al ervaren scouten, en dat zijn ze eigenlijk ook wel een beetje. Wouters worden zich steeds meer bewust van wat er rond hen gebeurt: op school, thuis en op de scouts. Ze krijgen ruimte en kansen om dingen uit te proberen en van elkaar te leren. Ze verleggen hun grenzen, halen kattenkwaad uit en kunnen vooral onbezorgd spelen.',
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
                'default_end_hour' => '17:00',
                'description' => 'Wie tussen 8 en 11 jaar is, kan meespelen bij de welpen of kabouters (wouters). Zij voelen zich vaak al ervaren scouten, en dat zijn ze eigenlijk ook wel een beetje. Wouters worden zich steeds meer bewust van wat er rond hen gebeurt: op school, thuis en op de scouts. Ze krijgen ruimte en kansen om dingen uit te proberen en van elkaar te leren. Ze verleggen hun grenzen, halen kattenkwaad uit en kunnen vooral onbezorgd spelen.,',
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
                'optional_mobile' => true,
                'default_end_hour' => '17:30',
                'description' => 'Niet oud, maar ook niet zo jong meer. In ieder geval in volle ontwikkeling. Ze mogen al alleen op pad, leren sjorren, kaartlezen en gaan op tentenkamp, waar ze zelfs hun eigen potje leren koken! Jonggivers krijgen een waaier van mogelijkheden om mee te beslissen, zelf de handen uit de mouwen te steken en allerlei vaardigheden onder de knie te krijgen. Een tak boordevol nieuwe dingen!',
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
                'optional_mobile' => true,
                'default_end_hour' => '17:30',
                'description' => 'Niet oud, maar ook niet zo jong meer. In ieder geval in volle ontwikkeling. Ze mogen al alleen op pad, leren sjorren, kaartlezen en gaan op tentenkamp, waar ze zelfs hun eigen potje leren koken! Jonggivers krijgen een waaier van mogelijkheden om mee te beslissen, zelf de handen uit de mouwen te steken en allerlei vaardigheden onder de knie te krijgen. Een tak boordevol nieuwe dingen!',
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
                'default_end_hour' => '17:30',
                'description' => 'Givers trekken samen op, hun vrienden zijn heilig. Ze hechten heel veel belang aan de groepssfeer. Van hun leiding krijgen ze alle mogelijkheden, ervaringen, spanning en enthousiasme die ze alleen binnen scouting kunnen vinden. Maar givers hebben ook hun eigen willetje. Ze krijgen dan ook elke keer dat ietsje meer. Dat ietsje meer participatie, dat ietsje meer zelfstandigheid, dat ietsje meer uitdaging. Als je hier zit, ben je al een echte!',
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
                'default_end_hour' => '17:30',
                'description' => 'Givers trekken samen op, hun vrienden zijn heilig. Ze hechten heel veel belang aan de groepssfeer. Van hun leiding krijgen ze alle mogelijkheden, ervaringen, spanning en enthousiasme die ze alleen binnen scouting kunnen vinden. Maar givers hebben ook hun eigen willetje. Ze krijgen dan ook elke keer dat ietsje meer. Dat ietsje meer participatie, dat ietsje meer zelfstandigheid, dat ietsje meer uitdaging. Als je hier zit, ben je al een echte!',
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
                'default_end_hour' => '17:30',
                'description' => 'Jin, Jij en Ik een Noodzaak. Als jin sta je op een grens: die tussen lid en leiding. In hun eigen \'jonge\' stijl werken ze gekke activiteiten, projecten en zelfs hun kamp uit. Al doende leren ze samen te werken en verantwoordelijkheid op te nemen. Hieruit groeit engagement voor de groep en voor de samenleving. Het is een jaar waarin ze hun eigen zegje hebben, waar ze samen met hun medejin tot ideeën komen en ze samen uitwerken. De jin gaat op buitenlands kamp. Ze organiseren financiële activiteiten om dat te kunnen bekostigen. Ze mogen zelf hun kamp invullen en vorm geven. Jin, gun ze hun apenjaar!',
            ],

        ],
    ],

    'verhuur' => [
        'max_gebouw' => 60,
        'max_tenten' => 60,

        'prijzen' => array(2019 => 120, 2020 => 120, 2021 => 120, 2022 => 140, 2023 => 140, 2024 => 175, 2025 => 175, 2026 => 200, 2027 => 200),
        'prijs_extra_persoon_gebouw' => 7,
        'prijs_inbegrepen_personen' => 25,

        'waarborg_weekend' => 400,
        'waarborg_weekend_leiding' => 400,
        'waarborg_kamp' => 400,

        'prijs_tent_nacht' => 0,
        'prijs_tent_persoon' => 3.5,

        // Minimaal aantal overnachtingen waarbij tenten toegestaan izjn
        'tenten_min_nachten' => 0,

        'overlapping_grenzen_toelaten' => true,
    ],

    'contacts' => [
        'groepsleiding' => array(
            'name' => 'Groepsleiding',
            'mail' => 'groepsleiding@scoutssintjan.be',
        ),
        'kapoenen' => array(
            'name' => 'Kapoenen leiding',
            'mail' => 'kapoenen@scoutssintjan.be',
        ),
        'kabouters' => array(
            'name' => 'Kabouters leiding',
            'mail' => 'kabouters@scoutssintjan.be',
        ),
        'welpen' => array(
            'name' => 'Welpen leiding',
            'mail' => 'welpen@scoutssintjan.be',
        ),
        'jonggidsen' => array(
            'name' => 'Jonggidsen leiding',
            'mail' => 'jonggidsen@scoutssintjan.be',
        ),
        'jongverkenners' => array(
            'name' => 'Jongverkenners leiding',
            'mail' => 'jongverkenners@scoutssintjan.be',
        ),
        'gidsen' => array(
            'name' => 'Gidsen leiding',
            'mail' => 'gidsen@scoutssintjan.be',
        ),
        'verkenners' => array(
            'name' => 'Verkenners leiding',
            'mail' => 'verkenners@scoutssintjan.be',
        ),
        'akabe' => array(
            'name' => 'Akabeleiding',
            'mail' => 'akabe@scoutssintjan.be',
        ),

        'jin' => array(
            'name' => 'Jinleiding',
            'mail' => 'jin@scoutssintjan.be',
        ),

        'verhuur' => array(
            'name' => 'Verhuur verantwoordelijke',
            'mail' => 'verhuur@scoutssintjan.be',
        ),

        'inschrijving' => array(
            'name' => 'Inschrijvingen',
            'mail' => 'inschrijving@scoutssintjan.be',
        ),

        'verhuur' => array(
            'name' => 'Verhuur verantwoordelijke',
            'mail' => 'verhuur@scoutssintjan.be',
        ),

        'verhuur' => array(
            'name' => 'Verhuur verantwoordelijke',
            'mail' => 'verhuur@scoutssintjan.be',
        ),

        'vzw' => array(
            'name' => 'VZW',
            'permission' => 'contactpersoon_vzw',
        ),

        'verhuur' => array(
            'name' => 'Verhuur verantwoordelijke',
            'mail' => 'verhuur@scoutssintjan.be',
        ),

        'evenementen' => array(
            'name' => 'Evenementen',
            'mail' => 'evenementen@scoutssintjan.be',
        ),

        'pannenkoeken@scoutssintjan.be' => array (
            'name' => 'Pannenkoeken',
            'mail' => 'pannenkoeken@scoutssintjan.be'
        )
    ],
];

return array_merge($config, $private);
