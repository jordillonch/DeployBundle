<?php

function includeIfExists($file)
{
    if (file_exists($file)) {
        return include $file;
    }

    throw new \Exception(sprintf("File %s not found", $file), 1);

}

if (!$loader = includeIfExists(__DIR__.'/../vendor/autoload.php')) {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install --dev'.PHP_EOL);
}

$loader->add('JordiLlonch\\Bundle\\DeployBundle', __DIR__ . '/../');


