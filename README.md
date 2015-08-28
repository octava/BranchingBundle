# BranchingBundle

Symfony BranchingBundle. Auto change *mysql* database depends on current git branch.

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e0649e62-91e7-497c-8008-cf7aba6d0ee9/big.png)](https://insight.sensiolabs.com/projects/e0649e62-91e7-497c-8008-cf7aba6d0ee9)

Bundle version is connected with supported symfony version.

## Installation

Download bundle by composer

```
composer require octava/branching
```

Then, enable the bundle by adding the following line in the app/AppKernel.php file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new Octava\Bundle\BranchingBundle\OctavaBranchingBundle();

        // ...
    }

    // ...
}
```

Create new branch `git branch feature` or `git checkout -b feature`. 

After that run 'app/console' command, and bundle create and copy new database automatically.

> Be sure, that your mysql connect has privileges to create new scheme.
> Bundle use default symfony connection params 'database_host' etc.

### Configuration

```
# Default configuration for "BranchingBundle"

branching:
    switch_db: true     #enable or disable auto switch db
    copy_db_data: true  #copy db from root db
```

### Nginx example

Obviously, you're hosting must support dns name like this `*.test.my.project.com`. 
There is an example of nginx config for different branches:

```
server {
    #...
    
    if ($branch = "") {
        set $branch "master";
    }
    server_name ~^(www\.)?(?<branch>.+)\.test\.my\.project\.com$;
    root /www/test.my.project.com/project/$branch/web;
    
    #...
```

## Twig extensions

### Current branch

* Get current branch, useful for generating project title

```
#your twig template file

{{ current_branch() }}
```

For master branch return `master (dev)` string.
