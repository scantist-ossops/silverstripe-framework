# Themes

## Introduction

Themes are reusable packages of templates, CSS and images that you can use to
kick start your SilverStripe project.

## Downloading

Like modules, themes are installable using the Composer dependency management
library. A gallery of the themes you can install is viewable in the
[themes](http://extensions.silverstripe.org/themes) section of the SilverStripe
extensions site. Each theme has a page a link you can use to preview it.

## Installing

### Composer

The theme installation process is very similar to the module installation process.
This assumes you have set up SilverStripe using Composer (the default setup). If
you installed SilverStripe from a downloaded archive, please see the next
section for alternative setup instructions.

1.  Add the relevant requirement to the `composer.json` file in the root of your
    application.
2.  Run `composer.phar update` on the CLI in the root of of your application.
2.  Change your site configuration to use the new theme. You can do this by:
        - Placing the following in `application/_config.php`: `SSViewer::set_theme('<theme-name'>);`
        - Changing the theme in the Site Configuration panel in the CMS.
3.  Visit your homepage with `?flush=all` appended to the URL.

### Archive

If you did not install SilverStripe using Composer (e.g. you installed SilverStripe
from one of the archived release packages), you can also install a theme by
downloading it as a  archive and installing it. The link to do this is available
on the theme's page.

1.  Extract the contents of the theme archive into the `themes` directory in your
    SilverStripe installation. The theme should be in the directory `themes/<theme-name>`,
    and contain folders with names like "templates", "css", and "images".
2.  Change your site configuration to use the new theme. You can do this by:
        - Placing the following in `application/_config.php`: `SSViewer::set_theme('<theme-name'>);`
        - Changing the theme in the Site Configuration panel in the CMS.
3.  Visit your homepage with `?flush=all` appended to the URL.

## Developing your own theme

See [Developing Themes](theme-development) to get an idea of how themes actually
work and how you can develop your own.

## Submitting your theme to SilverStripe

If you want to submit your theme to the SilverStripe directory then check

*   You should ensure your templates are well structured, modular and commented
    so it's easy for other people to customise them.
*   Templates should not contain text inside images and all images provided must
    be open source and not break any copyright or license laws.  This includes
    any icons your template uses in the frontend or the backend CMS.
*   A theme does not include any PHP files. Only CSS, HTML, Images and
    JavaScript.

## Links

 * [Themes Listing on the SilverStripe Extensions site](http://extensions.silverstripe.org/themes)
 * [Themes Forum on silverstripe.org](http://www.silverstripe.org/themes-2/)
 * [Themes repository on github.com](http://github.com/silverstripe-themes)
