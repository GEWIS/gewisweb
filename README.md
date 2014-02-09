GEWIS Website
=============

Introduction
------------
Website for Study association GEWIS.

Installation
------------

- Clone the repository.
- Install dependencies using composer: `php composer.phar install`
- Create a new MySQL database.
- Copy `config/autoload/doctrine.local.php.dist` to `config/autoload/doctrine.local.php`
  and configure the database settings in it.
- Give the webserver's user read and write permissions to the `data/` directory.
- Run `./vendor/bin/doctrine-module orm:schema-tool:create` to populate the database.

### Optional debugging configuration

- Copy `config/autoload/zdt.local.php.dist` to `config/autoload/zdt.local.php`.
