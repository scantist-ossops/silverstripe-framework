# Upgrading

Since version 3.1, the process of upgrading SilverStripe is mainly running
the relevant Composer command. If you are upgrading from a pre-3.1 version,
please see the [3.1 upgrading](/changelogs/3.1.0#upgrading) guide for
information.

See the [upgrade notes and changelogs](/changelogs) for release-specific
information.

## Process

1.  Check if the modules that are part of your site are compatible.
2.  Backup your database content and application files.
3.  Update your application's `composer.json` file to reference the new
    SilverStripe version in the "require" block.
4.  Run `composer.phar update` in the root of your application.

<div class="warning" markdown="1">
Never update a website on the live server without trying it on a development copy first.
</div>

## Decision Helpers

How easy will it be to update my project? It's a fair question, and sometimes a
difficult one to answer.

*   "Micro" releases (x.y.z) are explicitly backwards compatible, "minor" and
    "major" releases can deprecate features and change APIs (see our
    [/misc/release-process](release process) for details)
*   If you've made custom branches of SilverStripe core, or any thirdparty
    module, it's going to be harder to upgrade.
*   The more custom features you have, the harder it will be to upgrade.  You
    will have to re-test all of those features, and adapt to API changes in
    core.
*   Customisations of a well defined type - such as custom page types or custom
    blog widgets - are going to be easier to upgrade than customisations that
    modify deep system internals like rewriting SQL queries.

## Related

*   [Release Announcements](http://groups.google.com/group/silverstripe-announce/)
*   [Blog posts about releases on silverstripe.org](http://silverstripe.org/blog/tag/release)
*   [/misc/release-process](Release Process)
