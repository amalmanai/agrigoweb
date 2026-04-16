<?php
$c = file_get_contents('composer.json');
$c = str_replace('"phpunit/phpunit": "^13.1"', '"phpunit/phpunit": "^11.0"', $c);
file_put_contents('composer.json', $c);
echo "Downgraded phpunit.\n";
