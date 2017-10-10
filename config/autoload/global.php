<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return [
    /**
     * Bcrypt cost.
     *
     * DO NOT CHANGE THE PASSWORD HASH SETTINGS FROM THEIR DEFAULTS
     * Unless A) you have done sufficient research and fully understand exactly
     * what you are changing, AND B) you have a very specific reason to deviate
     * from the default settings and know what you're doing.
     *
     * The password hash settings may be changed at any time without
     * invalidating existing user accounts.
     *
     * The number represents the base-2 logarithm of the iteration count used for
     * hashing. Default is 13 (about 20 hashes per second on an i5).
     */
    'bcrypt_cost' => 13,

    /**
     * IP address start for the TU/e. All IP addresses starting with this will
     * be allowed more base rights, like viewing exams
     */
    'tue_range' => '131.155.',

    'login_rate_limits' => [
        'normal' => [
            'user' => 10,
            'ip' => 100,
            'lockout_time' => 10
        ],
        'pin' => [
            'user' => 5,
            'ip' => 50,
            'lockout_time' => 20
        ],
    ],
    'storage' => [
        'storage_dir' => 'public/data',
        'public_dir' => 'data',
        'dir_mode' => 0777, // rwx by default
    ],

    /**
     * Exam and Summary upload directory configration.
     */
    'education' => [
        'upload_dir' => 'public/data/education',
        'public_dir' => 'data/education',
        'dir_mode' => 0777, // rwx by default
    ],

    /**
     * Exam and Summary temporary upload directory configration.
     */
    'education_temp' => [
        'upload_exam_dir' => 'public/data/education_temp_exams',
        'upload_summary_dir' => 'public/data/education_temp_summaries',
        'public_exam_dir' => 'data/education_temp_exams',
        'public_summary_dir' => 'data/education_temp_summaries',
    ],

    /**
     * Dreamspark configuration.
     */
    'dreamspark' => [
        'url' => 'https://e5.onthehub.com/WebStore/Security/AuthenticateUser.aspx?account=%ACCOUNT%&username=%EMAIL%&key=%KEY%&academic_statuses=%GROUPS%',
        // configured locally
        'account' => '',
        'key' => ''
    ],

    /**
     * CA Path for SSL certificates, override this locally if necessary.
     */
    'sslcapath' => '/etc/ssl/certs',

    /**
     * Path for JWT keypairs
     */
    'jwt_key_path' => 'data/keys/jwt-key',
    'jwt_pub_key_path' => 'data/keys/jwt-key.pub',

    /**
     * Photo's upload directory configuration
     */
    'photo' => [
        'upload_dir' => 'public/data/photo',
        'public_dir' => 'data/photo',
        'max_photos_page' => 20,
        'dir_mode' => 0777, // rwx by default
        'small_thumb_size' => [
            /*
             * Max. width and height which a thumbnail may have. Height param must be greater than width, for
             * landscape images.
             */
            'width' => 320,
            'height' => 640
        ],
        'large_thumb_size' => [
            /*
             * Max. width and height which a thumbnail may have.
             */
            'width' => 960,
            'height' => 1920
        ],
        'album_cover' => [
            'width' => 320,
            'height' => 180,
            'inner_border' => 2,
            'outer_border' => 0,
            'cols' => 2,
            'rows' => 2,
            'background' => '#ffffff'
        ]
    ],

    'organ_information' => [
        'cover_width' => 2000,
        'cover_height' => 625,
        'thumbnail_width' => 512,
        'thumbnail_height' => 288,
    ],

    'frontpage' => [
        'activity_count' => 3, // Number of activities to display
        'news_count' => 3, // Number of news items to display
    ],

    /**
     * Doctrine global configuration, like functions
     */
    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'numeric_functions' => [
                    'RAND'  => 'Application\Extensions\Doctrine\Rand'
                ]
            ]
        ]
    ],

    /**
     * OASE SOAP API configuration.
     *
     * Please not that it is impossible to use this API without a whitelisted
     * IP. Hence, it is only possible to use this on the GEWIS server. In 2013
     * we communicated with STU about using this API, instead of trying to
     * pull the information from OASE via HTTP. Which would lead to
     * impracticalities on our side, and a slow OASE at times on the side of
     * Dienst ICT.
     */
    'oase' => [
        'soap' => [
            'wsdl' => 'http://dlwtbiz.campus.test.tue.nl/ESB/ESB_ESB_DLWO_ESB_ReceivePort.svc?wsdl',
            'options' => [
                'classmap' => [
                    'Vraag' => 'Education\Oase\Vraag',
                    'Property' => 'Education\Oase\Property',
                    'Antwoord' => 'Education\Oase\Antwoord'
                ],
                'soap_version' => SOAP_1_1
            ]
        ],
        /**
         * Filters for studies
         */
        'studies' => [
            /**
             * Studies of W&I will have these keywords.
             *
             * Only studies with these keywords will be considered.
             */
            'keywords' => [
                "software science",
                "web science",
                "wiskunde",
                "informatica",
                "mathematics",
                "finance and risk",
                "information security technology",
                "(eit-sde)",
                "computational science and engineering",
                "statistics, probability, and operations research",
                "computer",
                "security",
                "business information systems",
                "embedded systems"
            ],
            /**
             * Negative keywords.
             *
             * Studies with these keywords will not be considered W&I studies.
             */
            'negative_keywords' => [
                'leraar',
                'natuurkunde'
            ],
            /**
             * Group ID's.
             *
             * Only studies with these group ID's will be considered.
             */
            'group_ids' => [
                100, // diverse masters
                110, // schakelprogramma's
                150, // minoren
                155, // HBO-minor
                200, // bachelor (pre-bachelor-college)
                210, // regulier onderwijs (incl. master)
                212, // coherente keuzepakketten wss
                250,
            ],
            /**
             * Education types
             */
            'education_types' => [
                'master',
                'bachelor'
            ]
        ]
    ]
];
