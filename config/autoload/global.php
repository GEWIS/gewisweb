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

use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Validator\HttpUserAgent;

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
        'cache_dir' => 'data/cache',
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
     * Settings for Monolog logger
     */
    'logging' => [
        'logfile_path' => 'data/logs/gewisweb.log',
        'max_rotate_file_count' => 10,
        'minimal_log_level' => 'INFO',
    ],

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
            'width' => 1920,
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

    'regulations' => [
        'activity-policy' => 'Beleid%20&%20Reglementen/Activiteitenbeleid',
        'borrel-reglement' => 'Beleid%20&%20Reglementen/Borrelreglement',
        'computer-reglement' => 'Beleid%20&%20Reglementen/Computerreglement',
        'sleutel-beleid' => 'Beleid%20&%20Reglementen/Sleutelbeleid',
        'poster-reglement' => 'Beleid%20&%20Reglementen/Posterbeleid',
    ],

    /**
     * Doctrine global configuration, like functions
     */
    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'numeric_functions' => [
                    'RAND' => 'Application\Extensions\Doctrine\Rand'
                ]
            ]
        ]
    ],

    'session_config' => [],

    'session_storage' => [
        'type' => SessionArrayStorage::class
    ],

    'session_manager' => [
        'validators' => [
            HttpUserAgent::class,
        ],
        'enable_default_container_manager' => true,
    ],
];
