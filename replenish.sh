#!/bin/sh
export REPOSITORY="GEWIS/gewisweb"
export BRANCH="master"
cd /tmp
apt-get update && apt-get install -y wget
wget --no-cache "https://github.com/${REPOSITORY}/archive/refs/heads/${BRANCH}.zip"
unzip master.zip
rm master.zip
cp -R -u gewisweb-master/public/* /code/public/
chown -R  www-data:www-data /code/public
cp -R -u gewisweb-master/data/* /code/data/
chown -R  www-data:www-data /code/data
rm -R /tmp/gewisweb-master
cd /code
./genclassmap.sh
./web orm:generate-proxies
