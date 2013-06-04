# D-Buddy

D-Buddy is a deployment server written in the Zibo framework.

## Features

* Deploy GIT repositories using SSH or FTP
* Manual or automatic deployment
* Flexible deploy configuration using projects, environments and servers
* Post hook commands for SSH deployments

## Installation

To install a version of D-Buddy, run the following commands:

    curl -sS https://raw.github.com/kayalion/zibo/master/install.sh | sh
    composer require dbud/core
    
If _composer_ is not installed globally, you can run:

    ./composer.phar require dbud/core
    
After all the modules are downloaded and installed, run the following commands to setup your database:

    php application/console.php parameter set app.title D-Buddy
    php application/console.php database connection register dbud mysql://username:password@host/database
    php application/console.php database connection create dbud
    php application/console.php orm define
    
D-Buddy is now installed, check the [configuration](https://github.com/kayalion/dbud/blob/master/manual/D-Buddy/Configuration.md) manual page for further instructions. 