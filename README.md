# Form Tools Core

[![Build Status](https://travis-ci.org/formtools/core.svg?branch=master)](https://travis-ci.org/formtools/core)

This repo contains the source code for the Form Tools Core. The Core provides the minimal code needed to run Form Tools
on your server. 

### Branches and releases

- The `master` branch contains the current ongoing work. Never assume it's stable, and never use code from this 
branch on your production environments - it's only for development.
- Code for specific versions have a branch by that name (e.g. `3.0.13`), but again, this isn't for consumption. The
releases section of githug contains code that's ready for use:
(https://github.com/formtools/core/releases)[https://github.com/formtools/core/releases]

### Form Tools 2 and 3

Form Tools 2 was the production version from 2010-2018. I took a long, 4-year break during that time to work on other
projects, but returned to the Form Tools world in 2016 to revamp the site and code. Form Tools 2 was entirely functional
code. Form Tools 3 (2018) was a complete rewrite of the application. Form Tools started back in 2004 and this was the third 
(and hardest!) rewrite. The broader goal was to simply modernize the script, but there were also some specific goals:
run on PHP 7, use PDO, update all lib dependencies up to date, introduce tests and pave the way for upcoming changes and
features.

#### Upcoming versions

- `3.0.x` - Misc updates paving the way for 3.1.
- `3.1` - rewriting the installation/upgrade code so that there's no longer hardcoded bundles of the Form Tools script including those modules/themes we choose, but instead you select whatever components your want during the installation, or later on. 
- `3.2` - revamping the UI. Totally new design, upgrading all the front-end code (i.e. moving to React)
- `3.3` - revamping the user permissions to make it roles-based and not have hardcoded "admin" and "client" accounts like now.

### PHP version support

- Everything up to *Form Tools Core 2.2.7* supports PHP 4.3 - 5.x.
- *Form Tools Core 3.0.0* supports PHP 5.3 and later.

### Documentation

- [https://docs.formtools.org/](https://docs.formtools.org/)

### Other Links

- [formtools.org](https://formtools.org/)
- [Twitter feed](https://twitter.com/formtools/)
- [Available Form Tools modules](https://modules.formtools.org/)
- [About Form Tools modules](https://docs.formtools.org/userdoc/modules/) 
- [Installation instructions](https://docs.formtools.org/userdoc/modules/installing/)
- [Upgrading](https://docs.formtools.org/userdoc/modules/upgrading/)

Development:
- (Tests)[TESTS.md]. Still in its infancy, but we're getting there.
- (Local Form Tools development)[DEVELOPMENT.md]. Learn about how to develop Form Tools locally here.
