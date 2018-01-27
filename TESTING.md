## Form Tools Tests

Alrighty! Test time.

It's very early days with testing Form Tools, but the ball is now rolling.  

Form Tools comes with PHPUnit tests which can be ran locally. The _Selenium_ tests are currently just being added 
and _only intended for automated testing on the main Core repo via Travis_. Down the road I'll open them up for local 
execution, but not yet. One thing at a time.

Only the Core currently has tests. I'll be adding them to modules later. 


#### Tags/Releases

The `.gitattributes` file in this repo specifies various files that are NOT included in the main release tags. This 
includes the `/tests` folder. Developers working on Form Tools would clone the repo anyway, so this only affects end 
users of the application.


#### The Composer /vendor folder

Notice that the repo DOES include a composer `/vendor` folder. This includes a few runtime dependency libraries, hence
why it was committed (and in the main releases). However, this folder only includes required dependencies, not 
dev dependencies. So to get phpunit you'll need to run a `composer install` without the `--no-dev` option (see below).

### Travis 

The goal is to hook up both the unit and end-to-end tests with Travis so they execute with every commit to master. 
Coming soon!

----------------

### PHPUnit 

Requirements: PHP 7. 

- Check out the repo and navigate to the root.
- Run `composer install`  
- Run `vendor/bin/phpunit`

----------------

### Selenium

Coming soon. 
