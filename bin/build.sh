#!/usr/bin/env bash

set -e

# Set variables.
PREFIX="refs/tags/v"
VERSION=${1#"$PREFIX"}
echo "Building Plugin v${VERSION}..."

# Build files
composer install --no-dev --prefer-dist
# Remove unwanted composer files
php bin/composer-fixer.php
# Patch to php70-pollyfill
# https://github.com/symfony/polyfill/pull/145
# Patches: https://gist.github.com/bshaffer/904b596beecdfbb0a39cb3cd07728720
# echo 'Adding PHP7 patch'
# curl https://gist.github.com/bshaffer/904b596beecdfbb0a39cb3cd07728720/raw > polyfill-php70.diff
# patch -p1 < polyfill-php70.diff
# rm -rf polyfill-php70.diff
# Install npm
npm install
npm run package
# Make Readme
echo 'Generate readme.'
curl -L https://raw.githubusercontent.com/fumikito/wp-readme/master/wp-readme.php | php

# Change version string.
sed -i.bak "s/ \* Version: .*/ * Version: ${VERSION}/g" ./wp-gianism.php
sed -i.bak "s/^Stable Tag: .*/Stable Tag: ${VERSION}/g" ./readme.txt
