# Modules

The SilverStripe Framework is designed to be a modular system - even the CMS is
simply a module that plugs into it.

A module is a collection of PHP code, templates, and other resources. In a
SilverStripe site, the core "framework", "cms", and even your site-specific
"application" module are modules.

SilverStripe's `[api:SilverStripe\Framework\Manifest]` system will find any
classes, configuration or template files in modules.

Modules provide the re-usable building blocks that allow you to quickly build
functionality, and also allow for separation of concerns. For more information
on creating modules, see [module-development](/topics/module-development).

## Installing Modules

Since SilverStripe 3.1 the preferred way to install modules has been with the
Composer dependency management library.

### Composer Installation

Since SilverStripe 3.1 [Composer](http://getcomposer.org) is used to install
modules, themes, and thirdparty dependencies. Composer is a tool that allows
you to specify the modules you require for your site, and will automatically
resolve dependencies and install them.

In order to install a module, all you need to do is add the relevant "require"
line to the `composer.json` file in the root of your project. As an example,
the require line for the `dev-master` version of the SQLite3 module would be:

	"require": {
		"silverstripe-labs/sqlite3": "dev-master"
	}

Once this has been added you run `composer.phar update` in the root of your
application, and this will automatically download the module, register it,
mirror any public asset files and rebuild the database.

You can also install other thirdparty libraries using Composer. More information
is available in the [Composer documentation](http://getcomposer.org/doc/00-intro.md).

### Alternative Installation

If you do not wish to use composer to install modules, or wish to add a custom
module, you can also add the module files and explicitly register the module
in your Application class.

The first step is to place the module files in the root of your application. If
the module is provided as an archive, extract it to the root of your application.
In your application root you should have a `<module-name>` folder alongside your
`application` and other directories.

The next step is to register the module. This is done by adding a register call
to the `registerModules` method in your custom application class. This is
normally located in `application/src/Application.php`. If the application you
installed has a custom `ModuleInterface` implementation, the relevant code
to add is:

	$this->getModules()->add(new CustomModuleClass());

Otherwise, you need to register the module by supplying the module name and
path relative to the application root:

	$this->getModules()->addFromDetails('module-name', 'module-path');

Once this is done, you need to rebuild the database. This is done by visiting
the `/dev/build?flush=all` page on your site in a web browser. Once this is
done your module should be up and running.

You can also use this method to create custom modules as part of your
application, by creating a directory alongside your `application` directory and
registering it.

## Finding Modules

The official source for modules is the extensions site. However, there are
also modules available from other sources, such as [GitHub](https://github.com).
It is strongly recommended that module authors submit their modules to the
extensions site.

*   [Official SilverStripe Extensions Site](http://extensions.silverstripe.org)
*   [SilverStripe GitHub Page](https://github.com/silverstripe)

## Module Architecture

Each application in SilverStripe has an application class, which registers the
modules that the application uses, along with their paths and other information.
This information is contained within an object that implements the
`[api:SilverStripe\Framework\Core\ModuleInterface]` interface. These are grouped
together into a `[api:SilverStripe\Framework\Core\ModuleSet]` instance.

Each module can define it's own custom implementation of the module class it is
represented by. In fact, it is recommended to do so, since it means that the
information does not have to be set by the user. An example implementation could
be:

	:::php
	use SilverStripe\Framework\Core\ModuleInterface;
	
	class ExampleModule implements ModuleInterface {
	
		/**
		 * Gets the name of the module - a unique identifier which is used to
		 * load module assets and refer to the module. Should be composed of
		 * lowercase letters and dashes.
		 */
		public function getName() {
			return 'example-module';
		}
	
		/**
		 * Gets the absolute path to where this module is installed. This can
		 * be determined by reflecting on the current file.
		 */
		public function getPath() {
			return dirname(__DIR__);
		}
	
		/**
		 * Returns the directory names which contain public assets. These will
		 * be mirrored across to the webroot.
		 */
		public function getAssetDirs() {
			return array('css', 'images', 'javascript');
		}
	
	}

## Registering Modules

Each SilverStripe application has a ModuleSet object, which contains a list of
all the modules used. This information can be loaded in three ways.

### Application Class

The most straightforward way is to directly register modules in the overloaded
`registerModules` in your application's application class:

	:::php
	protected function registerModules() {
		parent::registerModules();
	
		$this->getModules()->add(new CustomModuleClass());
		$this->getModules()->addFromDetails('module-name', 'module-path', ModuleSet::TYPE_MODULE);
	}

### YAML File

The second way is to load a set of modules from a YAML file. This is the default
wait of registering modules - the file is regenerated when a module is installed
using Composer. The `[api:SilverStripe\Framework\Core\ModuleSet::addFromYaml]`
method.

	:::php
	protected function registerModules() {
		// ...
		$this->getModules()->addFromYaml('modules.yml');
	}

	:::yaml
	modules:
	  -
	    class: Module\\CustomModuleClass
	  -
	    name: module-name
	    path: path/to/module
	    type: (module|theme|widget)

### Directory Scanning

The third way is to automatically scan a directory for modules (or themes). This
is mainly included for backwards compatibility, and is not suggested. Two
method can be used: `addFromDirectory()` and `addThemesFromDirectory()`. Both
take an absolute path, or path relative to the application root as an argument.

This will scan all subdirectories for modules or themes. The first method will
register any directories with a `_config.php` file or `_config` directory as
a module. The add themes from directory method will register all subdirectories

*   [Modules](/topics/module-developement)
*   [Module Release Process](/misc/module-release-process)
