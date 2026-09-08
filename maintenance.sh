#!/usr/bin/env bash

set -euo pipefail

git pull
php artisan migrate --seed
npm run build
rsync -av --delete ~/domains/taglogistica.devtru.online/taglogistica/public/build/ ~/domains/taglogistica.devtru.online/public_html/build/
