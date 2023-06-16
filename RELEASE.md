# Plugin Release Guideline

When you're ready to release the plugin, please follow the instructions below:

1.  Bump version
1.  Run build
1.  Add changelog
1.  Commit
1.  Tag and push

## 1. Bump the version

The version number is being used in various files, the `/bin/bump-version.sh` file makes it easier to update across the files.

**Usage:**

```bash
# ./bin/bump-version.sh <VERSION_NUMBER>
./bin/bump-version.sh 0.3.1
```

Here `0.3.1` is the version number. Once ran, it'll change the version numbers and will add a sample changelog entry in the `readme.txt` file.

## 2. Run build

The build script will generate the CSS and JS files, language files, etc. for production.

```bash
yarn build
```

## 3. Add Changelog

Changelog is very crucial for customers. Make sure the changelog is human readable and understood by the customers and non-technical users. The `readme.txt` file will have a sample changelog entry from the `bump-version` command above, add details here.

## 4. Commit the changes

Once everything is done, we are ready to commit the changes. Commit the changes with a message like this:

```
:bookmark: bumping version to VERSION_NUMBER
```

Now push the changes to GitHub: `git push`

## 5. Tag & Push

In this last step, we need to tag our last commit so that it will trigger a deploy to WordPress.org. The `./bin/tag.sh` file makes it easier to tag and push the tag to GitHub. The tag name will be taken from the `Stable tag` in the `readme.txt` file.

```bash
./bin/tag.sh
```

Done!
