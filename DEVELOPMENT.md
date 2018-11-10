## Development 

> This PAGE is still in development! The irony! I'm in the midst of updating the whole build and dev process for Form Tools 3.1, so this is in flux.  


Form Tools 3.1 (still in development) adds in Sass, React, JSX, webpack, grunt and some other goodies from your 
modern day developer's toolkit. As such the local development process had to be updated in various ways. Now the repo
is divided into the standard `src` / `dist` folder arrangement, where all source code is found in `src`, and the 
`dist` folder contains the build artifacts: minimized files, generated CSS, bundles and so on. 

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

Only ever edit code in the `/src` folder! Mark the generated `/dist` folder as a build artifact in your IDE so you don't
accidentally start editing these files.

Lastly, boot up Form Tools in your browser. Note: the development environment requires that you add in an extra /dist
folder - that's what you want to be looking at in your web browser, e.g. 

```
http://localhost:8888/core/dist
```


### Creating prod bundles

Coming soon. 