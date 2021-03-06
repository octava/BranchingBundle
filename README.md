# BranchingBundle

Symfony BranchingBundle. Auto change *mysql* database depends on current git branch.

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/c21d49cc-9a55-4e84-bcbe-98e30e624614/big.png)](https://insight.sensiolabs.com/projects/c21d49cc-9a55-4e84-bcbe-98e30e624614)

Bundle version is connected with supported symfony version.

## Installation

Download bundle by composer

```
composer require octava/branching
```

Then, enable the bundle by adding the following line in the app/AppKernel.php file of your project:

```php
<?php
// config/bundles.php
return [
    // ...
    Octava\Bundle\BranchingBundle\OctavaBranchingBundle::class => ['all' => true],
    // ...
}
```

Create new branch `git branch feature` or `git checkout -b feature`. 

After that run 'app/console' command, and bundle create and copy new database automatically.

> Be sure, that your mysql connect has privileges to create new scheme.
> Bundle use default symfony connection params 'database_host' etc.

### Configuration

Default configuration for "BranchingBundle"

```yaml
# config/packages/octava_branching.yaml

octava_branching:
    switch_db:
        enabled: true
        connection_urls:
            - '%env(resolve:DATABASE_URL)%'
            - '%env(resolve:BACKEND_DATABASE_URL)%'
        ignore_tables:
            - error_log
            - resend_log
    alter_increment_map:
        default:
            'UserBundle:User':
                test:
                    start: 50000000
                    step: 1000
                dev:
                    start: 8000000
                    step: 1000
            'BalanceBundle:BalanceOperation': ~
            'partner': ~
```

Configuration for dev

```yaml
#config/packages/dev/octava_branching.yaml
octava_branching:
    switch_db:
        enabled: true
```

### Nginx example

Obviously, you're hosting must support dns name like this `*.test.project.com`. 
There is an example of nginx config for different branches:

```
server {
    #...
    
    if ($branch = "") {
        set $branch "master";
    }
    server_name ~^(www\.)?(?<branch>.+)\.test\.project\.com$;
    root /www/test.my.project.com/project/$branch/web;
    
    #...
```

## Twig extensions

### Current branch

* Get current branch, useful for generating project title

```
#your twig template file

{{ octava_current_branch() }}
```

For master branch return `master (dev)` string.
