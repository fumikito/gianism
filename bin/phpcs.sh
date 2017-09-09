#!/usr/bin/env bash

vendor/bin/phpcs --config-set installed_paths $(pwd)/vendor/wp-coding-standards/wpcs
vendor/bin/phpcs --standard=phpcs.ruleset.xml $(find ./app -name '*.php')
vendor/bin/phpcs --standard=phpcs.ruleset.xml functions.php
