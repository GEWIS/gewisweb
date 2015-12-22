GEWIS Website
=============

Website for Study association GEWIS.

[![Build Status](https://travis-ci.org/GEWIS/gewisweb.png)](https://travis-ci.org/GEWIS/gewisweb)  [![Code Climate](https://codeclimate.com/github/GEWIS/gewisweb/badges/gpa.svg)](https://codeclimate.com/github/GEWIS/gewisweb)

Installation
============

- Clone the repository.
- Install dependencies using composer: `php composer.phar install`
- Create a new MySQL database.
- Copy `config/autoload/doctrine.local.php.dist` to
  `config/autoload/doctrine.local.php` and configure the database settings in
  it.
- Give the webserver's user read and write permissions to the `data/` and
  `public/data/` directories.
- Run `./vendor/bin/doctrine-module orm:schema-tool:create` to populate the
  database.
- Set a cronjob for generating the photo of the week `0 0 * * 1 ./web photo weeklyphoto >/dev/null 2>&1`
- Copy `config/autoload/local.php.dist` to `config/autoload/local.php`

Configuration for scss editing
------------------------------

If you need to edit stylesheets, you need to be able to compile scss files
locally. For that, follow the next steps:

- Install nodejs and npm (npm should be bundled with node).
- Install Ruby and the Sass gem `gem install sass`
- Install grunt-cli globally, `npm install -g grunt-cli`
- Install local dependencies (in the directory of the GEWIS Website), `npm install`

To compile scss files, simply run `grunt css`. While working on the
stylesheets, it may be nice to let grunt watch the stylesheet and
automatically recomple. For this, you can run `grunt watch`.

Configuration for emails
------------------------

Edit `config/autoload/local.php` and configure the email, using a ZF2 email
transport. By default, it uses the `File` transport to put email into the
`data/mail/` folder. On the GEWIS server, the following configuration should
actually send emails to people:

```php
'email' => [
    'transport' => 'Smtp',
    'options' => [
        'name' => 'gewis.nl',
        'host' => 'smtp.tue.nl',
        'port' => 25
    ],
    'from' => 'web@gewis.nl'
]
```

Optional debugging configuration
--------------------------------

Copy `config/autoload/zdt.local.php.dist` to `config/autoload/zdt.local.php`.

Dreamspark configuration (only works on the GEWIS-server)
---------------------------------------------------------

Edit `config/autoload/local.php` and configure the Dreamspark credentials.

OASE
====

For the exam archive, the website can communicate with OASE to obtain course
information. To update the course information, use the following command:

```bash
./web oase update
```

It is recommended to run this command as a cronjob. Since OASE doesn't update
much, once a month should be enough.

Also, the console routes `oase show course <code>` and `oase show studies` are
available for debugging purposes. These will directly contact OASE to obtain
the information.

JavaScript URL Helper
=====================

If you need to create dynamic routes within JavaScript code, we have the
JavaScript URL Helper for that. Here we have two components.

First, to be able to use a route in JavaScript code, you need to tell the
ScriptUrl view helper to expose that URL to the JavaScript URL helper. You do
this in the view where you need the JavaScript URL helper. For example:

```php
<?php
// for a route `home` without any parameters
$this->scriptUrl()->requireUrl('home');

// for a route `activity/show` with a parameter `id`
$this->scriptUrl()->requireUrl('activity/show', ['id']);
```

Now, in JavaScript code (even in `*.js` files), you can use these routes
almost like you are using the normal URL view helper:

```javascript
text = '<a href="' + URLHelper.url('home') + '">';

// or with parameters:
text = '<a href="' + URLHelper.url('activity/show', { id: 32 }) + '">';

```

Translation
===========

The website is translated using `gettext`. By default, all strings are written
in English.

Code
----

In the code, string should be translated with the translator, which can be
obtained with the `ServiceManager`. Then, strings should be translated as
follows:

```php
<?php
// obtain the translator
$translator = $sm->get('translator');
echo $translator->translate('Some text');
```

Views
-----

Most translation actually happens in views. Thus, there is a translate view
helper to help translation in views along. In views, you don't have to worry
about translator setup, you can simply use `$this->translate()` to translate
strings.

Translate helper
----------------

In the main directory of the project, there is a `translate-helper` shell
script. If the script is executed, all strings which are translated by the
translator, will be gathered in `module/Application/language/gewisweb.pot`.
From here, the language files (which are located in
`module/Application/language/`) should be updated by running `msgmerge lang.po
gewisweb.pot`. This updates the existing language file with newly added
strings.

Translation
-----------
The translation files `en.po` and `nl.po` should be translated with an editor
like POEdit. When saved, these will be converted to the binary `*.mo` files
used by the translator.

