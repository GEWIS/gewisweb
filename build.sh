#!/bin/bash

if [[ "$APP_ENV" == "development" ]]
then
    pecl install -f xdebug-2.5.5
fi

if [[ "$APP_ENV" == "production" ]]
then
    ./genclassmap.sh
    ./web orm:generate-proxies
fi
