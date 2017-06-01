## Form Tools Core

This repo contains the source code for the Form Tools Core. The Core provides the minimum application
code to run Form Tools on your server. At this point (April 2017), this is not the case: it also requires you to include
the [Core Field Types](https://github.com/formtools/module-core_field_types) module. This will change: that module 
will be moved to the Core. It's fine that it's a separate component, but it belongs in this repo.


### Repo

- `master` contains ongoing work for whatever next version of Form Tools. Right now it's 3.0.0. 
- check out the release sections for earlier, stable releases. 


### Where we're at (June, 2017)

Earlier this year I returned to work on Form Tools after a long (4 year!) break. There have been a lot of changes made 
to PHP and the various code dependencies that Form Tools relies on. The goal for the 3.0.0 rewrite is:

- *Run on PHP 7*.
- Upgrade Smarty (necessary for PHP 7 compatibility)
- Convert the codebase to object oriented. 
- move database interaction to use *PDO*.
- include Composer (just the basics right now, but all modules, themes etc. may end up as composer
components... not sure yet)
 

### PHP version compatibility

- Everything up to *Form Tools Core 2.2.7* supported PHP 4.3 - 5.x.
- *Form Tools Core 3.0.0* requires PHP 5.3 or later.


### Installation

Don't install the code by itself. Download a main package from the https://formtools.org/download/ page. 

If you want to run this repo as a standalone, right now you'll need to manually download and install the core-field-types 
module.


### 3.0.0 Changes

- I'm dropping the `$g_unicode` for SQL queries which has been enabled by default since a very early version of Form Tools. 
I don't see the point of non-unicode queries, so unless I hear otherwise it'll be removed.
- I'm going to start including all language files with the primary download bundles (not here in this core repo). There's
really no value in NOT including them all. 
- A lot of the internals are changing:
    - the sessions content is totally revamped & data will be stored in different locations.
    - the hooks used to be tied to function name. Now we've gone object-oriented, the mapping has all changed .The 
    upgrade process will automatically update your database so it'll work seamlessly, but if you had custom hooks 
    written in your own modules they will no longer continue to function.


### Notes

- Composer is great, but I still want to distribute Form Tools in _packages_ and not require users to have to do any 
command-line nonsense to get the script running. As such, I'm going to commit the _vendor/_ folder with all dependencies
and omit the _composer.lock_ file.
- Any way to add in PSR-2 checking for code quality...? Maybe too early...? 
