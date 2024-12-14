<?php

/**
 * Global Configuration Override.
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

declare(strict_types=1);

use Application\Extensions\Doctrine\Rand;
use Application\Extensions\Doctrine\Year;
use Application\Router\LanguageAwareTreeRouteStack;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\Renderer\RendererConstant;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Validator\HttpUserAgent;

return [
    /**
     * Change the default route class (`TreeRouteStack`) with our custom implementation that can infer the locale from
     * the URL.
     */
    'router' => [
        'router_class' => LanguageAwareTreeRouteStack::class,
    ],

    /*
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
    'passwords' => [
        'bcrypt_cost' => 13,
        'min_length_user' => 12,
        'min_length_companyUser' => 16,
        'pwned_passwords_host' => getenv('PWNED_PASSWORDS_HOST'),
    ],

    /*
     * Subnets in use by the TU/e. All IP addresses in a listed subnet will be allowed more base rights, like being able
     * to download exams.
     *
     * Note: the subnets must be provided in CIDR format.
     */
    'tue_ranges' => [
        '131.155.0.0/16',
        '100.64.0.0/10',
    ],

    'login_rate_limits' => [
        'user' => 5,
        'company' => 5,
        'ip' => 50,
        'lockout_time' => 10,
    ],
    'storage' => [
        'storage_dir' => 'public/data',
        'public_dir' => 'data',
        'cache_dir' => 'data/cache',
        'dir_mode' => 0777, // rwx by default
    ],

    /*
     * Exam and Summary upload directory configuration.
     */
    'education' => [
        'upload_dir' => 'public/data/education',
        'public_dir' => 'data/education',
        'dir_mode' => 0777, // rwx by default
    ],

    /*
     * Exam and Summary temporary upload directory configuration.
     */
    'education_temp' => [
        'upload_exam_dir' => 'public/data/education_temp_exams',
        'upload_summary_dir' => 'public/data/education_temp_summaries',
        'public_exam_dir' => 'data/education_temp_exams',
        'public_summary_dir' => 'data/education_temp_summaries',
    ],

    /*
     * Path for JWT keypairs
     */
    'jwt_key_path' => 'data/keys/jwt-key',
    'jwt_pub_key_path' => 'data/keys/jwt-key.pub',

    /*
     * Settings for Monolog logger
     */
    'logging' => [
        'logfile_path' => 'data/logs/gewisweb.log',
        'max_rotate_file_count' => 10,
        'minimal_log_level' => 'INFO',
    ],

    /*
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
            'height' => 640,
        ],
        'large_thumb_size' => [
            /*
             * Max. width and height which a thumbnail may have.
             */
            'width' => 1920,
            'height' => 1920,
        ],
        'album_cover' => [
            'width' => 640,
            'height' => 360,
            'inner_border' => 2,
            'outer_border' => 0,
            'cols' => 2,
            'rows' => 2,
            'background' => '#ffffff',
        ],
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
        'activity-policy' => 'Policies%20&%20Regulations/Activity%20Policy',
        'alcohol-policy' => 'Policies%20&%20Regulations/Alcohol%20Policy',
        'board-policies' => 'Policies%20&%20Regulations/Board%20Policies',
        'borrel-policy' => 'Policies%20&%20Regulations/Borrel%20Policy',
        'house-rules' => 'Policies%20&%20Regulations/House%20rules',
        'ict-policy' => 'Policies%20&%20Regulations/ICT%20Policy',
        'key-policy' => 'Policies%20&%20Regulations/Key%20Policy',
        'poster-policy' => 'Policies%20&%20Regulations/Poster%20Policy',
        'privacy-policy' => 'Policies%20&%20Regulations/Privacy%20Policy',
    ],

    'php-diff' => [
        'differ' => [
            // show how many neighbor lines
            // Differ::CONTEXT_ALL can be used to show the whole file
            'context' => Differ::CONTEXT_ALL,
            // ignore case difference
            'ignoreCase' => false,
            // ignore line ending difference
            'ignoreLineEnding' => false,
            // ignore whitespace difference
            'ignoreWhitespace' => false,
            // if the input sequence is too long, it will just gives up (especially for char-level diff)
            'lengthLimit' => 2000,
            // if truthy, when inputs are identical, the whole inputs will be rendered in the output
            'fullContextIfIdentical' => true,
        ],
        'renderer' => [
            // how detailed the rendered HTML is? (none, line, word, char)
            'detailLevel' => 'word',
            // renderer language: eng, cht, chs, jpn, ...
            // or an array which has the same keys with a language file
            // check the "Custom Language" section in the readme for more advanced usage
            'language' => 'eng',
            // show line numbers in HTML renderers
            'lineNumbers' => false,
            // show a separator between different diff hunks in HTML renderers
            'separateBlock' => true,
            // show the (table) header
            'showHeader' => false,
            // convert spaces/tabs into HTML codes like `<span class="ch sp"> </span>`
            // and the frontend is responsible for rendering them with CSS.
            // when using this, "spacesToNbsp" should be false and "tabSize" is not respected.
            'spaceToHtmlTag' => false,
            // the frontend HTML could use CSS "white-space: pre;" to visualize consecutive whitespaces
            // but if you want to visualize them in the backend with "&nbsp;", you can set this to true
            'spacesToNbsp' => false,
            // HTML renderer tab width (negative = do not convert into spaces)
            'tabSize' => 4,
            // this option is currently only for the Combined renderer.
            // it determines whether a replace-type block should be merged or not
            // depending on the content changed ratio, which values between 0 and 1.
            'mergeThreshold' => 1.0,
            // this option is currently only for the Unified and the Context renderers.
            // RendererConstant::CLI_COLOR_AUTO = colorize the output if possible (default)
            // RendererConstant::CLI_COLOR_ENABLE = force to colorize the output
            // RendererConstant::CLI_COLOR_DISABLE = force not to colorize the output
            'cliColorization' => RendererConstant::CLI_COLOR_AUTO,
            // this option is currently only for the Json renderer.
            // internally, ops (tags) are all int type but this is not good for human reading.
            // set this to "true" to convert them into string form before outputting.
            'outputTagAsString' => false,
            // this option is currently only for the Json renderer.
            // it controls how the output JSON is formatted.
            // see available options on https://www.php.net/manual/en/function.json-encode.php
            'jsonEncodeFlags' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            // this option is currently effective when the "detailLevel" is "word"
            // characters listed in this array can be used to make diff segments into a whole
            // for example, making "<del>good</del>-<del>looking</del>" into "<del>good-looking</del>"
            // this should bring better readability but set this to empty array if you do not want it
            'wordGlues' => [' ', '-'],
            // change this value to a string as the returned diff if the two input strings are identical
            'resultForIdenticals' => null,
            // extra HTML classes added to the DOM of the diff container
            'wrapperClasses' => ['diff-wrapper'],
        ],
    ],

    /*
     * Doctrine global configuration, like functions
     */
    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'numeric_functions' => [
                    'RAND' => Rand::class,
                    'YEAR' => Year::class,
                ],
            ],
        ],
    ],

    'session_config' => [],

    'session_storage' => [
        'type' => SessionArrayStorage::class,
    ],

    'session_manager' => [
        'validators' => [
            HttpUserAgent::class,
        ],
        'enable_default_container_manager' => true,
    ],
];
