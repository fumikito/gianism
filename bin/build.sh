#!/usr/bin/env bash

set -e

# Build files
composer install --no-dev
# Remove unwanted composer files
php bin/composer-fixer.php
# Install npm
npm install
npm run package
# Make Readme
echo 'Generate readme.'
curl -L https://raw.githubusercontent.com/fumikito/wp-readme/master/wp-readme.php | php
# Patch to php70-pollyfill
# https://github.com/symfony/polyfill/pull/145
# Patches: https://gist.github.com/bshaffer/904b596beecdfbb0a39cb3cd07728720
curl https://gist.github.com/bshaffer/904b596beecdfbb0a39cb3cd07728720/raw > polyfill-php70.diff
patch -p1 < polyfill-php70.diff
rm pollyfill-php70.diff
# Remove files
rm -rf .browserlistrc
rm -rf .gitignore
rm -rf .git
rm -rf .travis.yml
rm -rf node_modules
rm -rf tests
rm -rf bin
rm -rf phpcs.ruleset.xml
rm -rf phpunit.xml.dist
rm -rf phpdoc.xml
