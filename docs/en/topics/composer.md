# Composer

Since 3.1, SilverStripe has used [Composer](http://getcomposer.org) for
dependency management, and for installing modules and themes.

<div class="info" markdown="1">
For general information on what Composer is and how to use it, see the
[Composer Documentation](http://getcomposer.org)
</div>

## Configuration

SilverStripe uses various keys in the "extra" section in `composer.json`:

`silverstripe-application-dir`
:   The directory relative to `composer.json` where the application code is
    stored. Defaults to "application".

`silverstripe-application-class`
:   The class name of the application class for the current application. This
    defaults to "Application".

`silverstripe-modules-file`
:   The file name within the application directory which a YAML list of
    installed modules is written to.

## Modules

For more information on using Composer with modules you create, please see the
[modules](modules) and [module development](module-development) pages. A minimal
example `composer.json` file for a module is:

	:::js
	{
		"name": "vendor/mymodule",
		"description": "A description of what the module does",
		"type": "silverstripe-module",
		"require": {
			"silverstripe/framework": "3.1.*"
		},
		"autoload": {
			"psr-0": {
				"SilverStripe\\MyModule": "src/"
			}
		},
		"extra": {
			"silverstripe-module-class": "SilverStripe\\MyModule\\MyModule"
		}
	}

For a full list of available fields that you can set in the `composer.json` file,
see the [schema](http://getcomposer.org/doc/04-schema.md) documentation.

## Repositories

SilverStripe has its own Composer repository which is part of the
[SilverStripe Extensions](http://extensions.silverstripe.org) site. SilverStripe
modules, widgets and themes should be submitted here rather than to Composer's
[Packagist](http://packagist.org). SilverStripe also uses components from the
[Zend Framework](http://packages.zendframework.com/). For a SilverStripe project,
the "repositories" section of `composer.json` should look like:

	:::js
	"repositories": [
		{
			"type": "composer",
			"url": "http://extensions.silverstripe.org"
		},
		{
			"type": "composer",
			"url": "http://packages.zendframework.com"
		}
	]

## Scripts

SilverStripe performs a number of actions when Composer is used to install,
remove and update a package. These will automatically update the `modules.yml`
file in the application directory, install the module assets to the public
directory, and build the database.
