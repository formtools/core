# Form Tools Core

This repo contains the source code for the Form Tools Core. The Core provides the minimal code needed to run Form Tools
on your server. The `master` branch contains the ongoing work on Form Tools 3, currently in *alpha*. Check out the release sections for earlier, stable releases. 

### Where we're at (Sept, 2017)

Earlier this year I returned to work on Form Tools after a long (4 year!) break. There have been a lot of changes made 
to PHP and the various code dependencies that Form Tools relies on. The goal for the 3.0.0 rewrite is:

- *Run on PHP 7*.
- Upgrade Smarty
- Convert the codebase to object oriented
- move database interaction to use PDO
 
### PHP version compatibility

- Everything up to *Form Tools Core 2.2.7* supports PHP 4.3 - 5.x.
- *Form Tools Core 3.0.0* supports PHP 5.3 and later.

### Installation

- For Form Tools 2 (stable), see the installation instructions here: https://docs.formtools.org/installation/
- For Form Tools 3 (alpha), download the alpha package from https://formtools.org/download/alpha.php

### Upgrading

- For Form Tools 2, see the upgrade instructions here: https://docs.formtools.org/upgrading/
- For Form Tools 3, until the alpha version is a little further along, just download the latest version from the download page above and overwrite your old installation files.


## What's changed in 3.0.0

Form Tools 3 looks virtually identical to 2 - the bulk of the changes are behind the scenes. But there *are* a few small visual/functional changes:

- I dropping the `$g_unicode` for SQL queries which has been enabled by default since a very early version of Form Tools. 
I don't see the point of non-unicode queries, so unless I hear otherwise it'll stay removed.
- All language files are now included in the main repo. "Get more..." link in the UI are removed. 
- A lot of the internals have changed:
    - the hooks used to be tied to function name. Now we've gone object-oriented, the mapping has all changed. The 
    upgrade process (when completed) will automatically update your database so it'll work seamlessly, but if you had custom hooks written in your own modules they will no longer continue to function.

- Folder path for "edit" section of forms now in /edit subfolder, like /add.
- Default max file upload size setting changed from 200KB to 500KB.
- When creating a new form, the `Add Submission Button` setting would have a default label of `{$LANG.word_add_rightarrow}`.
The idea here was that by entering language string placeholders, users logging into the interface with different languages would see that button label localized in their own country. Interesting idea, weird implementation! I'm now going to be setting that value (and others) to be in the current language - "Add &raquo;" for English, in this case. The fields can _still_ accept language placeholders for the tiny percentage of people that need this feature, but it'll be much clearer for the vast bulk of users that don't need this feature.
- The core-field-types module has been incorporated into the Core code. The module no longer exists as a standalone.
- The `$g_delete_module_folder_on_uninstallation` configuration option has been dropped. Now the uninstall step just 
uninstalls the script. I'll add a separate UI option to allow you to try to remove the module files.

### Notes

- Composer is great, but I still want to distribute Form Tools in _packages_ and not require users to have to do any 
command-line nonsense to get the script running. As such, I'm going to commit the _vendor/_ folder with all dependencies
and omit the _composer.lock_ file.
- Keep the very un-OO user/administrator class code for 3.0. Will refactor that all anyway for user roles in 4.0.
- combine all that displayPage/displayModulePage etc. code.

### TOo fix:

- Minor bug: I was logged in on the Edit Client
page. I deleted the config file & reinstalled. After clicking the "login" button on the last page of the installation 
process, it took me to the Edit Client page again throwing a bunch of errors.
  -- installation should wipe out sessions on the first page, I think. 
  -- check that page with passing invalid client IDs. Should fail gracefully.
