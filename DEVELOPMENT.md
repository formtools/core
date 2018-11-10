## Form Tools Development 

Form Tools 3.1 (still in development) added Sass, React, JSX, webpack, grunt and some other goodies commonly used  
in any modern web developer's toolkit. Up until this version there really WAS no local development process: you just 
cloned the repo and booted it up in your browser. This is because Form Tools has always primarily been a PHP application; 
the front end was very basic.   

So anyway, the old "process" was super simple but no longer possible now we need to update the front end tools, so it's 
changed quite a bit with 3.1.  

The repo files are now grouped into the standard `src` / `dist` folder arrangement (only src is checked into the repo); 
all source code is found in `src` and build artifacts are found in `dist`: minimized files, generated CSS,
bundles and so on. Since PHP isn't really affected by any of this, it's found in both. There's a grunt command (see below)
that generates your dist folder: for PHP and other file, it simply copies them over from src to dist. Other files are 
actually processed and generated into dist. 


### Setting up dev environment

To develop Form Tools locally you need to be running *Node* and *npm*, *yarn*, *grunt*, plus you need a server running 
`PHP 5.3` or later with `PDO` and `MySQL`. Necessary minimum versions:

- Node >= 8.11.3
- Yarn (anything pretty new)
- PHP 5.3

For the server stuff, MAMP/LAMP/WAMP is your friend:
- Windows: http://www.wampserver.com/en/
- Mac: https://www.mamp.info/en/

These are preconfigured Apache, PHP, MySQL servers. PDO comes installed by default in any modern PHP setup so it's almost
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


### Creating prod bundle process

This is something only done by me. Nuisance is that npm doesn't have support for the exact process, so it's currently 
manual. See: https://stackoverflow.com/questions/38935176/how-to-npm-publish-specific-folder-but-as-package-root

I'll automate it when it becomes annoying / error prone. But for now...

1. Check the version and release date have been updated in `Core.class.php`
2. Generate the prod version with `grunt prod`
3. Make a copy of the `dist` folder somewhere.
4. Switch to the release branch `git pull release`. 
5. Wipe out all the old files (not .gitignore, .gitattributes)
6. Copy in the new files & commit them.
7. Go to github & make the new release. 
8. Add the new version in the Form Tools CMS.