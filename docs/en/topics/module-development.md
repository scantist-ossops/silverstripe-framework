# Module Development

Creating a module is a good way to allow re-using code and templates across
projects, or to separate parts of your application. SilverStripe by default
comes with two modules - the core "framework" and the "cms" module. If you're
wanting to add generic functionality that isn't specific to your project, you
can create a module:

1.  Create another directory in your application root directory alongside the
    main `application` directory.
2.  Register the module in your application classes `registerModules()` method.
3.  Inside your new module directory, follow the relevant
    [directory structure guidelines](/topics/directory-structure)

## Tips

Try and keep your module as generic as possible - for example if you're making
a forum module, your members section shouldn't contain fields like 'Games You
Play' or 'Your LiveJournal Name' - if people want to add these fields they can
sub-class your class, or extend the fields on to it.

## Publication

If you wish to submit your module to our public directory, you take responsibility
for a certain level of code quality, adherence to conventions, writing
documentation, and releasing updates. See the [contributing](/misc/contributing)
page for more information.

SilverStripe's module system is integrated with [Composer](http://getcomposer.org).
If you wish to submit your module, widget or theme to the
[SilverStripe Extensions site](http://extensions.silverstripe.org) your must
create a `composer.json` file in your module's directory. This file describes
your module, as well as listing the other dependencies it requires.

More information on `composer.json` files is available in the
[Composer documentation](http://getcomposer.org/doc/02-libraries.md). There are
many extra fields available.

The "type" of silverstripe extensions must be one of "silverstripe-module",
"silverstripe-widget" or "silverstripe-theme" to be installed. An example
`composer.json` file for an example module is:

	:::js
	{
		"name": "example-vendor/example-vendor",
		"description": "An example SilverStripe module",
		"type": "silverstripe-module",
		"keywords": ["example"],
		"require": {
			"php": ">=5.3.2",
			"silverstripe/framework": "dev-composer"
		},
		"autoload": {
			"psr-0": { "SilverStripe\\ExampleModule": "src" }
		}
	}

It is suggested that you use PSR-0 style autoloading inside your module, but
this is not required.

If you use git, hg or svn for your module, Composer will automatically determine
version information. See the [Composer documentation](http://getcomposer.org)
for more details. It is required to use version control to submit your module
to the extensions site.

### Custom Module Classes

<div class="warning" markdown="1">
If you provide a custom module, this MUST be loadable using one of the Composer
autoloading strategies.
</div>

Internally, each module is represented by an object that implements the
`[api:SilverStripe\Framework\Core\ModuleInterface]` interface. This provides
the following information via methods:

`getName()`
:   Returns the unique alphanumeric name of the module.

`getPath()`
:   Returns the absolute path to the module.

`getAssetDirs()`
:   Returns an array on directories within the module that contain

It is strongly recommended to define a custom implementation of this interface
for your module. This means that the name, path, and asset dirs can be set by
the module author rather than the user. In order to do this, you first create
the implementation of the interface, and then declare it in your `composer.json`
file:

	:::js
	"extra": {
		"silverstripe-module-class": "CustomModuleClass"
	}

An example custom module class is available on the
[module architecture](modules#module-architecture) page.

## Links

*   [SilverStripe Extensions](http://extensions.silverstripe.org)
*   [Modules](modules)
*   [Debugging](/topics/debugging)
