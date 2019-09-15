## Form Tools Tests

Alrighty! Test time.

It's very early days with testing Form Tools, but the ball is now rolling. 

Form Tools comes with PHPUnit tests for the PHP and jest tests for the FE code. 



#### Tags/Releases

The `.gitattributes` file in this repo specifies various files that are NOT included in the main release tags. This 
includes the `/tests` folder. Developers working on Form Tools would clone the repo anyway, so this only affects end 
users of the application.


#### The Composer `/vendor` folder

Notice that the repo DOES include a composer `/vendor` folder. This includes a few runtime dependency libraries, hence
why it was committed (and in the main releases). However, this folder only includes required dependencies, not 
dev dependencies. So to get phpunit you'll need to run a `composer install` without the `--no-dev` option (see below).

#### Travis 

Right now only the unit tests are running on travis. But the goal is to add in selenium tests as well.

----------------

### PHPUnit 

Requirements: PHP 7 has to be running on your machine's command line.

- Check out the repo and navigate to the root.
- Run `composer install`  
- Run `vendor/bin/phpunit`

### Jest

```
yarn install
yarn test
```
