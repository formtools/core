# Form Tools Core

[![Build Status](https://travis-ci.org/formtools/core.svg?branch=master)](https://travis-ci.org/formtools/core)

This repo contains the source code for the Form Tools Core. The Core provides the minimal code needed to run Form Tools
on your server. 

Form Tools 3 (2018) was a complete rewrite of the application. Form Tools started back in 2004 and this v3 the third 
(and hardest!) rewrite. The goal was to modernize the script: to run on PHP 7, use PDO, update all lib dependencies,
introduce tests and pave the way for upcoming changes and features.

Form Tools 2 was the production version from 2010-2018. I took a long, 4-year break during that time to work on other
projects, but returned to the Form Tools world in 2016 to revamp the site and code. Form Tools 2 is entirely
functional code; FT3 converted everything to object-oriented.

*PHP version support*

- Everything up to *Form Tools Core 2.2.7* supports PHP 4.3 - 5.x.
- Form Tools Core 3.0.0 supports PHP 5.3 and later.

## Branches 

- The `master` branch contains the latest work being done on 3.1. `master` is not production ready.
- `3.0.x` (3.0.1, 3.0.2, 3.0.3...) contain the tip of the various releases, but they're tagged and put into final
releases when complete. Still, handy place to get the latest 3.0.x version if you need it. 

### Upcoming versions

- `3.1` - rewriting the installation/upgrade code so that there's no longer hardcoded bundles of the Form Tools script
including those modules/themes we choose, but instead you select whatever components your want during the installation, 
or later on. 
- `3.2` - revamping the UI. Totally new design, upgrading all the front-end code (i.e. moving to React)
- `3.3` - revamping the user permissions to make it roles-based and not have hardcoded "admin" and "client" accounts like
now.


### Tests

Check out TESTS.md. Still in its infancy, but we're getting there.


### Documentation

- [https://docs.formtools.org/](https://docs.formtools.org/)


### Local Development

Form Tools 3.1 adds in sass, React, JSX and some other goodies. As such the local development process had to be
updated to automatically boil these various code down to the raw CSS and JS.

To develop Form Tools locally you need to be running *node*, *yarn* and *grunt*. 

You can install yarn in different ways, but `brew` is your friend. Try this, but failing that just google it.  

```
brew install yarn
```

Once yarn is running, clone this repo to somewhere on your computer. Note: you'll need to be running a PHP 
server like MAMP, WAMP or LAMP and MySQL must be available. 

```
git clone https://github.com/formtools/core.git

```

Next, in your terminal, navigate to the downloaded folder and run:


```
yarn install
grunt
```

The `grunt` command is for local development. It generates a `/dist` folder in your Form Tools root folder alongside
the existing `/src` folder. There it copies the content of your /src folder. It also sets up watchers to generate 
sass into your dist, and boots up webpack to generate the React/es6 code and generate *that* into your dist folder too. 

Only ever edit code in the `/src` folder! Blacklist the generated `/dist` folder in your IDE so you don't accidentally
start editing copied over files.

Lastly, boot up Form Tools in your browser. Note: the development environment requires that you add in an extra /dist
folder - that's what you want to be looking at in your web browser. 

```
http://localhost:8888/core/dist
```



### Other Links

- [formtools.org](https://formtools.org/)
- [Twitter feed](https://twitter.com/formtools/)
- [Available Form Tools modules](https://modules.formtools.org/)
- [About Form Tools modules](https://docs.formtools.org/userdoc/modules/) 
- [Installation instructions](https://docs.formtools.org/userdoc/modules/installing/)
- [Upgrading](https://docs.formtools.org/userdoc/modules/upgrading/)

















