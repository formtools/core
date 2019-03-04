# Development


### Generating release package

With 3.0.14 we publish everything to a dist/ folder (not checked in). So publishing has to work differently than 
before, where I manually created a release via github's UI. 
 
Now:
- Check out second repo. Give the folder the name `formtools_releases`.
- Check out `releases` branch. 
- In the first repo, run `grunt publish x.y.z`: that (will) generate the `dist/` folder, copy it into the other repo
and create a tag with the name specified. 

At that point we can just generate the new release through github as before.  