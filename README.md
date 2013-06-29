# Deploy Symfony2 Bundle

WIP


## Installation

### Using composer

Add following lines to your `composer.json` file:


#### Symfony 2.3

    "require": {
      ...
      "jordillonch/deploy-bundle": "dev-master"
    },
    "minimum-stability": "dev",

Execute:

    php composer.phar update

Add it to the `AppKernel.php` class:

    new JordiLlonch\Bundle\DeployBundle\JordiLlonchDeployBundle(),



## Config

    local_repository_dir



## Test

Add testserver1 and testserver2 as 127.0.0.1 as localhost in your /etc/hosts
Configure sshd to accept PubkeyAuthentication


## TODO

- Tests
- Helpers (cache warmer, composer update, github diffs url...)
- Ssh with Process component
- Verbose mode

