#!/usr/bin/env bash

# Exit if any command fails.
set -e

# Change to the expected directory.
cd "$(dirname "$0")"
cd ..
DIR=$(pwd)
BUILD_DIR="$DIR/build/flywp"

# Enable nicer messaging for build status.
BLUE_BOLD='\033[1;34m'
GREEN_BOLD='\033[1;32m'
RED_BOLD='\033[1;31m'
YELLOW_BOLD='\033[1;33m'
COLOR_RESET='\033[0m'

error() {
    echo -e "\n${RED_BOLD}$1${COLOR_RESET}\n"
}
status() {
    echo -e "\n${BLUE_BOLD}$1${COLOR_RESET}\n"
}
success() {
    echo -e "\n${GREEN_BOLD}$1${COLOR_RESET}\n"
}
warning() {
    echo -e "\n${YELLOW_BOLD}$1${COLOR_RESET}\n"
}

status "💃 Time to build the FlyWP ZIP file 🕺"

# remove the build directory if exists and create one
rm -rf "$DIR/build"
mkdir -p "$BUILD_DIR"

# Run the build.
# status "Installing dependencies... 📦"
# npm install

status "Generating build... 👷‍♀️"
yarn build

# Copy all files
status "Copying files... ✌️"
FILES=(flywp.php readme.txt assets views includes languages composer.json composer.lock)

for file in ${FILES[@]}; do
    cp -R $file $BUILD_DIR
done

# Install composer dependencies
status "Installing dependencies... 📦"
cd $BUILD_DIR
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Remove unnecessary files
rm composer.json composer.lock assets/css/tailwind-base.css

# go one up, to the build dir
status "Creating archive... 🎁"
cd ..
zip -r -q flywp.zip flywp

# remove the source directory
rm -rf flywp

success "Done. You've built FlyWP! 🎉 "
