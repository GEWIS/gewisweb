#!/bin/sh
# Remove the old files
rm -rf /code/public/publicarchive/*

# Download the new files
sshpass -p "${SSH_PASSWORD}" sftp -o StrictHostKeyChecking=no "${SSH_USERNAME}"@"${SSH_REMOTE}" <<!
cd "/datas/Public Archive/"
mget -r * /code/public/publicarchive/
quit
!

# Give new files proper attributes
chown -R www-data:www-data /code/public/publicarchive/
