# Form Tools Core

This repo contains the source code for the Form Tools Core. The Core provides the minimal code needed to run Form Tools
on your server. That will need to be uploaded you your `/modules` folder.

The other modules, themes and API all just expand on the core functionality to provide additional, optional features
should you need them.


### Repo

- `master` contains ongoing work for whatever next version of Form Tools. Right now it's 3.0.0. 
- check out the release sections for earlier, stable releases. 


### Where we're at (Sept, 2017)

Earlier this year I returned to work on Form Tools after a long (4 year!) break. There have been a lot of changes made 
to PHP and the various code dependencies that Form Tools relies on. The goal for the 3.0.0 rewrite is:

- *Run on PHP 7*.
- Upgrade Smarty (necessary for PHP 7 compatibility)
- Convert the codebase to object oriented.
- move database interaction to use *PDO*.
- include Composer (just the basics right now, but all modules, themes etc. may end up as composer components... not sure yet)
 

### PHP version compatibility

- Everything up to *Form Tools Core 2.2.7* supports PHP 4.3 - 5.x.
- *Form Tools Core 3.0.0* supports PHP 5.3 and later.


### Installation

Don't install the code by itself. Download a main package from the https://formtools.org/download/ page. 


### 3.0.0 Changes

- I'm dropping the `$g_unicode` for SQL queries which has been enabled by default since a very early version of Form Tools. 
I don't see the point of non-unicode queries, so unless I hear otherwise it'll be removed.
- All language files are now included in the main repo. "Get more..." link in the UI are removed. 
- A lot of the internals are changing:
    - the sessions content is totally revamped & data will be stored in different locations.
    - the hooks used to be tied to function name. Now we've gone object-oriented, the mapping has all changed .The 
    upgrade process will automatically update your database so it'll work seamlessly, but if you had custom hooks 
    written in your own modules they will no longer continue to function.

- Folder path for "edit" section of forms now in /edit subfolder, like /add.
- Default max file upload size setting changed from 200KB to 500KB.
- When creating a new form, the `Add Submission Button` setting would have a default label of `{$LANG.word_add_rightarrow}`.
The idea here was that by entering language string placeholders, users logging into the interface with different languages would 
see that button label localized in their own country. Cool idea, weird implementation! I'm now going to be setting that 
value (and others) to be in the current language - "Add &raquo;" for English, in this case. The fields can _still_ accept
language placeholders for the tiny percentage of people that need this feature, but it'll be much clearer for the bulk of 
users that don't need this feature.
- The core-field-types module has been incorporated into the Core code. The module no longer exists as a standalone.

### Notes

- Composer is great, but I still want to distribute Form Tools in _packages_ and not require users to have to do any 
command-line nonsense to get the script running. As such, I'm going to commit the _vendor/_ folder with all dependencies
and omit the _composer.lock_ file.
- Any way to add in PSR-2 checking for code quality...? Maybe too early...? 
- error handling for scenario where DB is gone but config exists.
- Keep the very un-OO user/administrator class code for 3.0. Will refactor that all anyway for user roles in 4.0.
- Still need to solve `g_success`, `g_message`...
- combine all that displayPage/displayModulePage etc. code.

### TODO
 
- Database sessions.
- still doing a redirect to (root) /install when removing config.php
- after installation, shows a "no sessions" error on login page (confirm)
- list of available translations wasn't set after fresh install
- move translations to composer package
- upgrading from FT2.x (for later alpha version - perhaps the first Beta?)


### Nice to haves
- add grunt task to add/remove specific keys from language files (translations repo)
