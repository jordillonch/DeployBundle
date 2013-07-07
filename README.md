# Symfony2 Deploy Bundle

**WIP**

[![Build Status](https://secure.travis-ci.org/jordillonch/JordiLlonchDeployBundle.png?branch=master)](http://travis-ci.org/jordillonch/JordiLlonchDeployBundle)

## What it is?

This bundle aims to be a deploy system for your projects.

It is a Symfony2 Bundle but it can be used to deploy several kind of projects.

The bundle provides some commands to automatize deploy process. Here main commands:

* **Initialize**: Prepare deployer and remote servers creating a directories structure to host new code.
* **Download**: Download code from repository, adapt, warn up… and send to remote servers in order to put new code to production.
* **Code to production**. Deploy new code to production atomically and reload web server, app…
* **Rollback**. Return back to previous deployed version.

Deployer have zones configured to deploy new code.

Zones are a project and environment (e.g. prod_api, our project Api for the production environment).

Deployer use GitHub repository, a configured branch, and HEAD as a target to deploy.


You can use this bundle adding it to your projects via composer (see installation section) but my recommendation is that you create a new project to deploy because you may be want to not have your production configurations in your repository project. So it is a good idea to delegate add productions configuration to the deploy system.


## How it works?

There are two basic ideas that allows to deploy several code versions and put one of them to production and rollback between them.


When you want to deploy new code you have to do a "*download*" operation. That operation clones code from your git repository to a new directory (e.g. 20130704_180131_a618a56b08549794ec4c9d5db29058a01a58977f) then do adaptations to the code. Adaptations are the step where configurations are added, app cache is warned up, dependencies are downloaded and installed, symlinks are created to shared directories… 

Shared directories are directories where are data that you want to keep between deploys. (e.g. logs, reports, generated images...)

After that, code is copied to configured servers. Ssh authorized keys are used to allow copy and execute commands to remote servers.


Then, when you want to use last downloaded code to production you have to execute "*code to production*" operation. This operation modify a symlink to the directory where last version are downloaded. 

Usually, after that you should restart webserver or php-fpm. 


*Note*: These ideas are taken from Capistrano.


## Quick start


### Create a new Symfony 2.3 project
 

```    
php composer.phar create-project symfony/framework-standard-edition path/ 2.3.0
```

    
### Install the bundle

Add following lines to your `composer.json` file:

```
"require": {
  "jordillonch/deploy-bundle": "dev-master"
},
"minimum-stability": "dev",
```
Execute:

```
php composer.phar update
```

Add it to the `AppKernel.php` class:

```
new JordiLlonch\Bundle\DeployBundle\JordiLlonchDeployBundle(),
```
    


### Configure general settings and a zone

```
jordi_llonch_deploy.config:
    project: MyProject
    mail_from: iamrobot@me.com
    mail_to:
        - me@me.com
    local_repository_dir: /home/deploy/local_repository
    clean_before_days: 7
jordi_llonch_deploy.zones:
    prod_myproj:
        deployer: myproj
        environment: prod
        urls:
            - deploy@testserver1:8822
            - deploy@testserver2:8822
        checkout_url: 'git@github.com:myrepo/myproj.git'
        checkout_branch: master
        repository_dir: /var/www/production/myproj/deploy
        production_dir: /var/www/production/myproj/code
```


### Create a deploy class to myproj

* First create your own bundle:

```
php app/console generate:bundle --namespace=MyProj/DeployBundle --dir=src --no-interaction
```

* Create your own class to deploy:

```
src/MyProj/DeployBundle/Service/Test.php:

<?php

namespace MyProj/DeployBundle/Service;

use JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer;

class Test extends BaseDeployer
{
    public function downloadCode()
    {
        $this->logger->debug('Downloading code...');
        $this->output->writeln('<info>Downloading code...</info>');
        $this->downloadCodeGit();

        $this->logger->debug('Adapting code');
        $this->output->writeln('<info>Adapting code...</info>');
        // here you can download vendors, add productions configuration, do cache warm up, set shared directories...

        $this->logger->debug('Copying code to zones...');
        $this->output->writeln('<info>Copying code to zones...</info>');
        $this->code2Servers();
    }

    public function downloadCodeRollback()
    {
    }

    protected function runClearCache()
    {
        $this->logger->debug('Clearing cache...');
        $this->output->writeln('<info>Clearing cache...</info>');
        $this->execRemoteServers('sudo pkill -USR2 -o php-fpm');
    }
}
```

* Add your deploy class as a service with tag: `jordi_llonch_deploy`

```
<service id="myproj.deployer.test" class="MyProj/DeployBundle/Service/Test">
    <tag name="jordi_llonch_deploy" deployer="myproj"/>
</service>
```

* `myproj` is used in the configuration as `deployer` value.
            

### Allow ssh access to remote servers

It is necessary to add the public key of the deploy user to `.ssh/authorized_keys` in remote servers in order to allow access to the deploy system.


### Initialize

After configure the deployer you have to do the initialization.

```
app/console deployer:initialize --zones=prod_myproj
```


### Download

Now you can download code from your repository and copy to your servers.

```
app/console deployer:download --zones=prod_myproj
```


### Code to production

After download you just need to put code into production.

```
app/console deployer:code2production --zones=prod_myproj
```


### Rollback

If there is a problem and you need to go back to a previous version you have to do 2 steps:

1) Ask deploy for available versions to rollback.

```
app/console deployer:rollback list --zones=prod_myproj
```

2) Execute rollback to specific version

```
app/console deployer:rollback execute [version] --zones=prod_myproj
```



## Commands

### initialize

### download

### code2production

### rollback

### status

### configure

### clean


## Configuration

local_repository_dir


### Services


## Extra

### Proxy


### Composer

#### github-oauth

~/.composer/config.json



## Test

Add testserver1 and testserver2 as 127.0.0.1 as localhost in your /etc/hosts
Configure sshd to accept PubkeyAuthentication


## TODO

- Verbose mode
- Tests
- Helpers (cache warmer, composer update, github diffs url...)
- Ssh with Process component
- Abstract layer for VCS


## Author

Jordi Llonch - llonch.jordi at gmail dot com


## License

DeployBundle is licensed under the MIT License. See the LICENSE file for full details.

