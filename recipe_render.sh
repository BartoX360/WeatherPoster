#!/bin/sh
# Zippe la recette, l'importe dans LaraPaper comme « WeatherPoster (test) » et rapatrie les rendus dans $1
set -e
DIR=$(cd "$(dirname "$0")" && pwd)
(cd "$DIR/recipe/src" && rm -f ../weatherposter.zip && zip -q -r ../weatherposter.zip .)
scp -q "$DIR/recipe/weatherposter.zip" "$DIR/recipe_test.php" root@192.168.1.110:/tmp/
ssh root@192.168.1.110 'docker cp /tmp/weatherposter.zip larapaper-app-1:/tmp/ && docker cp /tmp/recipe_test.php larapaper-app-1:/tmp/ && docker exec -u www-data larapaper-app-1 php artisan tinker /tmp/recipe_test.php 2>&1 | grep -E " ok$|Exception|Error|url=|live payload"; rm -rf /tmp/wpr && docker cp larapaper-app-1:/tmp/wpr /tmp/wpr'
mkdir -p "$1"; scp -q 'root@192.168.1.110:/tmp/wpr/*' "$1/"
