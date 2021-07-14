#!/bin/sh
export REPOSITORY="GEWIS/gewisweb"
export BRANCH="master"
cd /tmp
apt-get update && apt-get install -y wget
wget --no-cache "https://github.com/${REPOSITORY}/archive/refs/heads/${BRANCH}.zip"
unzip "${BRANCH}.zip"
rm "${BRANCH}.zip"
cp -R -u gewisweb-${BRANCH}/public/* /code/public/
chown -R  www-data:www-data /code/public
cp -R -u gewisweb-${BRANCH}/data/* /code/data/
chown -R  www-data:www-data /code/data
rm -R /tmp/gewisweb-${BRANCH}
cd /code
php composer.phar dump-autoload -o --no-dev
./vendor/doctrine/doctrine-module/bin/doctrine-module orm:generate-proxies
