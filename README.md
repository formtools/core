# Form Tools Core

[![Build Status](https://travis-ci.org/formtools/core.svg?branch=master)](https://travis-ci.org/formtools/core)

This repo contains the source code for the Form Tools Core. The Core provides the minimal code needed to run Form Tools
on your server. 

### Branches 

- The `master` branch contains the latest work being done on 3.1. `master` is not production ready.
- `3.0.x` (3.0.1, 3.0.2, 3.0.3...) contain the tip of the various releases, but they're tagged and put into final releases when complete. Still, handy place to get the latest 3.0.x version if you need it. 

### Form Tools History

Form Tools 3 (2018) was a complete rewrite of the application. Form Tools started back in 2004 and this was the third 
(and hardest!) rewrite. The goal was to modernize the script: to run on PHP 7, use PDO, update all lib dependencies, introduce tests and pave the way for upcoming changes and features.

Form Tools 2 was the production version from 2010-2018. I took a long, 4-year break during that time to work on other projects, but returned to the Form Tools world in 2016 to revamp the site and code. Form Tools 2 is entirely functional code; FT3 converted everything to object-oriented.

### PHP version support

- Everything up to *Form Tools Core 2.2.7* supports PHP 4.3 - 5.x.
- *Form Tools Core 3.0.0* supports PHP 5.3 and later.

### Upcoming versions

- `3.1` - rewriting the installation/upgrade code so that there's no longer hardcoded bundles of the Form Tools script including those modules/themes we choose, but instead you select whatever components your want during the installation, or later on. 
- `3.2` - revamping the UI. Totally new design, upgrading all the front-end code (i.e. moving to React)
- `3.3` - revamping the user permissions to make it roles-based and not have hardcoded "admin" and "client" accounts like now.

### Next Version notes

3.1 will be introducing a few changes that anticipate 3.2. A lot of the UI is a very old, using jQuery and jQuery UI 
to provide interactivity. 3.1 is adding in React, Redux, and a standard node build process using webpack. At this stage
I'm not totally sure how much of the interface will be converted to use React, but I do want to have it available for the 
(many!) pages in the interface that are interaction heavy. 

Up until now, the packaging process for Form Tools has been pretty much a simple matter of removing the odd file, then 
it up. This will change with 3.1. Now we're adding in source code that needs bundling, minification - i.e. a modern build
process - the packaging process will be handled by webpack. Again, I'm not 100% sure about what form this will take at 
this moment, but I have some ideas.   

### Tests

Check out TESTS.md. Still in its infancy, but we're getting there.


### Documentation

- [https://docs.formtools.org/](https://docs.formtools.org/)


### Other Links

- [formtools.org](https://formtools.org/)
- [Twitter feed](https://twitter.com/formtools/)
- [Available Form Tools modules](https://modules.formtools.org/)
- [About Form Tools modules](https://docs.formtools.org/userdoc/modules/) 
- [Installation instructions](https://docs.formtools.org/userdoc/modules/installing/)
- [Upgrading](https://docs.formtools.org/userdoc/modules/upgrading/)
