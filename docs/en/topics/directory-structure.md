# Directory Structure

## Introduction

This describes the default directory structure of a SilverStripe application when
installed using [Composer](http://getcomposer.org/). Most of the directory
structure can be customised by overloading the relevant methods in your application's
`[api:SilverStripe\Framework\Core\Application]` subclass.

## Structure

`composer.json`
:   The composer file which lives in the root of your application defines the
    dependencies for your project, as well as other information.

`application`
:   This contains your application's code and other files.

`public`
:   This is the web root of the application. This contains both user uploaded
    and module asset files (CSS, JS, etc). The `assets` directory inside this
    directory contains user-uploaded files. It also contains the main `index.php`
    file, which all incoming requests invoke.

`themes`
:   This directory contains themes, which are the templates and associated
    asset files bundled up into individual themes.

`vendor`
:   This directory contains the dependencies installed using Composer. This
    includes the framework, as well as any modules, widgets or themes you
    install. See the [Composer](http://getcomposer.org/) documentation for more
    details on installing dependencies using Composer.

## Application Structure

The `application` folder contains all of your application's code, and associated
files.

`application`
:   This contains your application's code and other files.

`application/src`
:   Contains the PHP classes the make up your application. It is strongly
    recommended to follow [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
    style file naming conventions.

`application/templates`
:   Contains the templates used for rendering the application. Most templates
    contain HTML, and have an `*.ss` extension.

`application/css`,
`application/images`,
`application/javascript`
:   These directories contain the public asset files (CSS, JS and images). These
    are mirrored across to the webroot directory to provide public access.

## Themes Structure

The `themes` directory contains the application themes, which contains bundles
of HTML, CSS, images, and other files used to render your site. Each theme is
contained within a subfolder in this directory. See the [themes](/topics/themes)
documentations for more information.

`themes/<theme-name>`
:   Each theme is contained in its own sub-directory.

`themes/<theme-name>/templates`
:   This directory contains the templates for the theme.

`themes/<theme-name>/css`
`themes/<theme-name>/images`
`themes/<theme-name>/javascript`
:   Contains the asset files for the theme. These are mirrored across to the
    webroot.

## Module Structure

Modules are structured in the same way as the main application directory. Common
files and directories include:

`composer.json`
:   SilverStripe uses Composer by default to install modules. Each module should
    have a composer file. More information is available in the
    [Composer](/topics/composer) topic.

`_config.php`,
`_config`
:   Contains the configuration settings that are part of the module, in either
    PHP or YAML format.

`src`
:   Contains the module's PHP code. This should preferably be structured using
    the PSR-0 standard.

`css`,
`images`,
`javascript`
:   Contains asset files which are mirrored to the webroot.

`lang`
:   Contains language definitions in YAML format.

`templates`
:   Contains the `*.ss` templates used for rendering information.

`tests`
:   Contains unit tests for module.
