## Development 

> This PAGE is still in development! The irony! I'm in the midst of updating the whole build and dev process for Form Tools 3.1, so this is in flux.  

Form Tools 3.1 (still in development) adds in Sass, React, JSX, webpack, grunt and some other goodies commonly used  
in any modern web developer's toolkit. Up until this version there really WAS no local development process: you just 
cloned the repo and booted it up in your browser. That was super easy but denied me all the useful tools and benefits 
of a proper build process, so as of 3.1 this has greatly changed. 

Now the repo is divided into the standard `src` / `dist` folder arrangement (only src is checked into the repo); 
all source code is found in `src` and the `dist` folder contains the build artifacts: minimized files, generated CSS,
bundles and so on.

The section below explains how to set up your dev environment, but here's a few high level notes first. 
- the `src` folder contains the source code. Only ever edit stuff the source code in there. 
- a `dist` folder is created by the build process (see below). This is always prod-ready. The release packages from
FT 3.1 and later contain the _content of the dist folder only_. 



### Setting up dev environment

To develop Form Tools locally you need to be running *node*, *yarn*, *grunt*, plus you need a server running `PHP 5.3` or 
later with `PDO` and `MySQL`. For the server stuff, MAMP/LAMP/WAMP is your friend:
- Windows: http://www.wampserver.com/en/
- Mac: https://www.mamp.info/en/

They're preconfigured Apache, PHP, MySQL servers. PDO comes installed by default in any modern PHP setup so it's almost
certainly just there already. If you're not sure it's available, just assume it is: when you first install 
Form Tools locally the installation script will whine at you if it's not. 

Next, you can install yarn in different ways, but `brew` is your friend. Try this (but failing that just google it):  

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

The `grunt` command generates a `/dist` folder in your Form Tools root folder alongside the existing `/src` folder. There
it copies the content of your /src folder. It also sets up watchers to generate sass into your dist, and boots up
webpack to generate the React/es6 code and generate *that* into your dist folder too.

_Only ever edit code in the `/src` folder!_ Mark the generated `/dist` folder as a build artifact in your IDE so you don't
accidentally start editing these files.

Lastly, boot up Form Tools in your browser. Note: the development environment requires that you add in an extra /dist
folder - that's what you want to be looking at in your web browser, e.g. 

```
http://localhost:8888/core/dist
```


### Creating prod bundles

This is something only done internally.