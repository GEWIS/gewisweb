#!/bin/sh
. /code/config/ssh.env
rsync -aWPu --delete --chown www-data:www-data --rsh="/usr/bin/sshpass -p ${SSH_PASSWORD} ssh -o StrictHostKeyChecking=no -l ${SSH_USERNAME}" files.gewis.nl:/home/public/* /code/public/publicarchive
