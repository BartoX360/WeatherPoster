#!/bin/sh
# Zippe la recette, l'importe dans LaraPaper comme « WeatherPoster (test) » et rapatrie les rendus dans $1
set -e
DIR=$(cd "$(dirname "$0")" && pwd); ROOT=$(cd "$DIR/.." && pwd)
[ -f "$DIR/.env" ] && . "$DIR/.env"
: "${LARAPAPER_SSH:?définir LARAPAPER_SSH (ex. root@mon-serveur), ou le mettre dans dev/.env}"
(cd "$ROOT/recipe/src" && rm -f ../weatherposter.zip && zip -q -r ../weatherposter.zip .)
scp -q "$ROOT/recipe/weatherposter.zip" "$DIR/recipe_test.php" "$LARAPAPER_SSH":/tmp/
ssh "$LARAPAPER_SSH" 'docker cp /tmp/weatherposter.zip larapaper-app-1:/tmp/ && docker cp /tmp/recipe_test.php larapaper-app-1:/tmp/ && docker exec -u www-data larapaper-app-1 php artisan tinker /tmp/recipe_test.php 2>&1 | grep -E " ok$|Exception|Error|url=|live payload"; rm -rf /tmp/wpr && docker cp larapaper-app-1:/tmp/wpr /tmp/wpr'
mkdir -p "$1"; scp -q "$LARAPAPER_SSH":'/tmp/wpr/*' "$1/"
