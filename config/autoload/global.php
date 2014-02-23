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

return array(
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
     * Email configuration.
     */
    'email' => array(
        'transport' => 'File',
        'options' => array(
            'path' => 'data/mail/'
        ),
        'from' => 'web@gewis.nl'
    ),

    /**
     * OASE SOAP API configuration.
     */
    'oase' => array(
        'soap' => array(
            'wsdl' => 'http://dlwtbiz.campus.test.tue.nl/ESB/ESB_ESB_DLWO_ESB_ReceivePort.svc?wsdl',
            'options' => array(
                'classmap' => array(
                    'Vraag' => 'Education\Oase\Vraag',
                    'Property' => 'Education\Oase\Property',
                    'Antwoord' => 'Education\Oase\Antwoord'
                ),
                'soap_version' => SOAP_1_1
            )
        )
    )
);
