# Execution Pipeline

This page documents how SilverStripe processes a request, from the initial
request to the outputted result.

## URL Rewriting

The web root for your application by default lives in the `public` directory,
and all incoming requests come into this directory. SilverStripe using URL
rewriting to rewrite all requests to the `index.php` file. In Apache, the URL
rewrite rules looks like (contained in `.htaccess`):

	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*)$ index.php [QSA,L]

These lines rewrite all URLs for files which do not exist to the `index.php`
file.

## Index.php

All URLs are rewritten so they invoke `index.php`, which lives in the web root.
This includes the Composer autoloader from the vendor directory, which means
that classes can be autoloaded. Control is then handed over to the custom
`Application` class.

## Application Class

Each SilverStripe application has an "application class", which is responsible
for setting up the environment and handling requests. The application class
implements to `[api:SilverStripe\Framework\Core\ApplicationInterface]` interface,
usually by subclassing `[api:SilverStripe\Framework\Core\Application]`.

The application class has a static `respond` method which reads the incoming
requests from the environment, handles them and outputs the result. The following
tasks are performed by the application class:

*   The modules that make up the application are registered.
*   The environment is bootstrapped.
*   The manifest, which loads classes, config information, and templates, is
    created.
*   The configuration object and dependency injector are created and
    initialised.
*   A database connection is established.

Control is then passed on to the `[api:Director]` class.

## Director

The `[api:Director]` is responsible for actually responding to the request. It
reads the incoming request, matches it to a controller, and then calls
`[api:Controller::run()]` to generate the result. `[api:Controller]`s are the
building blocks of your application.

## Templates

See [templates](/topics/templates) for information on the SilverStripe template
system, which is used for rendering most requests.
