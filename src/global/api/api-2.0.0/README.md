# Form Tools API

This repo contains the Form Tools API, providing some convenience methods to extend Form Tools in various ways:
- Let's you create complex, multi-page forms that integrate well with Form Tools. 
- Let's you display Form Tools data on your website publicly
- Provides ways to create user accounts, login to Form Tools programmatically
- Lots more. 

The API documentation is pretty thorough. Please refer to one of the following sections:

- [API v1.x](https://docs.formtools.org/api/) - current version, but soon to be legacy
- [API v2.x](https://docs.formtools.org/api/v2) - this is coming (very) soon

## API v1.x vs. v2.x

Form Tools 3 was a complete rewrite of the application, including changing the codebase from functional code to
object-oriented. The API v2.x was updated for Form Tools 3 compatibility, converting the API into a class.  
 
In terms of functionality, v1.x and v2.x of the API are identical: they (currently) provide the same methods. _What's
changed has the way you call the API methods_. 

## Examples

The documentation links above contains in-page example code for all the methods, but for something more hands-on
check out the `examples/` folder in this repo. That contains some simple, bare-bones examples (no CSS!) of some of the
API methods that can easily be shown. Other methods such as the form integration methods need additional work to
configure Form Tools - so couldn't be included. See the [tutorials](https://docs.formtools.org/tutorials/) for further 
info on them.

The`v1/` folder contains the old Form Tools 2.x compatible API methods (version 1.x of the API); the `v2/`
folder contains Form Tools 3 compatible API methods (version 2.x of the API). For Form Tools 3 users, please use
the `v2/` folder examples - `v1/` is just provided for people upgrading to Form Tools 3 and want to keep
compatibility with their existing API forms and usage.

### How to view the examples

- Create a form in Form Tools - it doesn't matter if it's an internal, external or Form Builder form. Many of
the Form Tools API methods rely on passing form IDs, view IDs and more. As such you may find you will need
to edit these examples to pass the IDs/settings that are correct for your installation and forms.
- The Form Tools download packages from (formtools.org)[https://formtools.org/download/] all include the API as 
part of the bundle, but if you're getting the code from the source github repos, be sure to upload the contents of 
the latest [API package version](https://github.com/formtools/api/releases) to your `[Form Tools root]/global/api/ folder`. 
- Edit the `examples-config.php` file in this folder to set the `$examples_enabled = true;` variable. The examples
are all disabled by default to prevent accidentally exposing your Form Tools submission data to the outside world.
If you're worried about this, just move the examples folder wherever you want on your server and change the paths 
to the api.php/API.class.php file. 

## Getting help

Check out the [Form Tools forums](https://forums.formtools.org.  If you think you've found a bug - please report it here 
on the github repo. Thanks!
