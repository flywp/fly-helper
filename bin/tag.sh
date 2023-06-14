#!/bin/bash
# Tag the current commit with the version number

# Read version number from readme.txt
VERSION=$(grep "^Stable tag" readme.txt | awk -F' ' '{print $NF}')

# if the tag already exists, exit
if git rev-parse "$VERSION" >/dev/null 2>&1; then
    echo "Error: Version $VERSION already exists. Please bump the version number in readme.txt"
    exit 1
fi

# if the repo is dirty, exit
if ! git diff --quiet; then
    echo "Error: There are uncommitted changes in the repo. Please commit or stash them before tagging."
    exit 1
fi

echo " > Tagging version $VERSION"
git tag -a "$VERSION" -m "Tagging version $VERSION"

echo " > Pushing tag to remote"
git push origin "$VERSION"

echo "Done!"
