#!/bin/bash
for d in module/*; do
 php vendor/zendframework/zendframework/bin/classmap_generator.php -l $d
done