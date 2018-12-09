## Form Tools Tests

Alrighty! Test time.

It's very early days with testing Form Tools, but the ball is now rolling. 

Form Tools comes with PHPUnit tests which can be ran locally. The _Selenium_ tests are currently just being added 
and _only intended for automated testing on the main Core repo via Travis_. Down the road I'll open them up for local 
execution, but not yet. One thing at a time.

Only the Core currently has tests. I'll be adding them to modules later. 


#### The Composer `/vendor` folder

Notice that the repo DOES include a composer `/vendor` folder in `/src`. This includes a few runtime dependency 
libraries, hence why it was committed (and in the main releases). However, this folder only includes required 
dependencies, not dev dependencies. So to get phpunit you'll need to run a `composer install` without the `--no-dev` 
option (see below).

#### Travis 

Right now only the unit tests are running on travis. But the goal is to add in selenium tests as well.

----------------

### PHPUnit 

Requirements: PHP 7 has to be running on your machine's command line.

- Check out the repo and navigate to the root.
- Run `composer install`  
- Run `vendor/bin/phpunit`


#### Test lang file content 

To verify the lang files are in sync and all contain the same set of keys, in your dev environment run the following
command: 

```
grunt i18n
```

That outputs details about missing strings found in the `en_us.php` but not in other files, plus any superfluous ones
found in other lang files but not in the English source file.

----------------

### Selenium

Coming soon. 
