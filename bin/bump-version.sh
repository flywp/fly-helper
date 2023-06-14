#!/bin/bash
# Bump version number in different files

RED="\033[1;31m"
GREEN="\033[0;32m"
YELLOW="\033[1;33m"
BLUE="\033[1;34m"
PURPLE="\033[1;35m"
CYAN="\033[1;36m"
WHITE="\033[1;37m"
RESET="\033[0m"

# take the version number from input
[ -z "$1" ] && {
    echo "Usage: $0 version"
    exit 1
}

VERSION=$1

echo "${YELLOW}Bumping version to $VERSION${RESET}"

# replace the version number in flywp.php
echo "${CYAN} ❯ Replacing version number in flywp.php"
sed -i '' -e "s/\* Version: .*/\* Version: $VERSION/" flywp.php
sed -i '' -e "s/public \$version = '.*';/public \$version = '$VERSION';/" flywp.php

# replace the version number in readme.txt
echo "${CYAN} ❯ Replacing version number in readme.txt"
sed -i '' -e "s/Stable tag: .*/Stable tag: $VERSION/" readme.txt

# add a changelog entry for the new version in readme.txt in the format: = vVersion (Date) =
echo "${CYAN} ❯ Adding changelog entry in readme.txt"
sed -i '' -e "s/== Changelog ==/== Changelog ==\n\n= v$VERSION ($(date '+%d %B, %Y')) =\n\n * **Fix:** Nothing changed yet./" readme.txt

# replace the version number in package.json
echo "${CYAN} ❯ Replacing version number in package.json"
sed -i '' -e "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" package.json

# replace the version number in composer.json
echo "${CYAN} ❯ Replacing version number in composer.json"
sed -i '' -e "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" composer.json

echo "${GREEN}Done!${RESET}"
