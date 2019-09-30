#! /usr/bin/env bash

function interrupt_code()
# This code runs if user hits control-c
{
  echored "\n*** Operation interrupted ***\n"
  exit $?
}

# Trap keyboard interrupt (control-c)
trap interrupt_code SIGINT

# Pretty printing functions
NORMAL=$(tput sgr0)
GREEN=$(tput setaf 2; tput bold)
RED=$(tput setaf 1)

function echored() {
    echo -e "$RED$*$NORMAL"
}

function echogreen() {
    echo -e "$GREEN$*$NORMAL"
}

# Store current directory path to be able to call this script from anywhere
DIR=$(dirname "$0")


# Check that we have PHP installed on this machine
if ! command -v php >/dev/null 2>&1
then
    echored "ERROR: PHP is not installed on your machine, PHP >=7.0 is required."
    echo "If you are on Debian/Ubuntu you can install it with 'sudo apt-get install php7'."
    exit 1
fi

cd $install

# Install Composer if not installed
if ! command -v composer >/dev/null 2>&1
then
    echogreen "Installing Composer (PHP dependency manager)"
    php -r "readfile('https://getcomposer.org/installer');" | php
    if [ ! -d vendor ]
    then
        echogreen "Installing PHP dependencies with Composer (locally installed)"
        php composer.phar install
    fi
else
    if [ ! -d vendor ]
    then
        echogreen "Installing PHP dependencies with Composer (globally installed)"
        composer install
    fi
fi

# Create json files used for stats
stats_file1=cache/stats_locales.json
stats_file2=cache/stats_requests.json

if [ ! -f $stats_file1 ]
then
    echogreen "Add $stats_file1 file"
    echo '{}' > $stats_file1
fi

if [ ! -f $stats_file2 ]
then
    echogreen "Add $stats_file2 file"
    echo '{}' > $stats_file2
fi

# Add Reference repository as upstream remote
if ! $(git remote | grep upstream &> /dev/null)
then
    origin=$(git config --get remote.origin.url)
    remote_https='https://github.com/mozfr/transvision.git'
    remote_git='git@github.com:mozfr/transvision.git'

    if [ $origin == $remote_https ] || [ $origin == $remote_git ]
    then
        echored "Your local clone is from the reference repository, you should clone your own fork if you intend to contribute code."
    else
        echogreen "$remote_git added as upstream remote"
        git remote add upstream $remote_git
        git fetch upstream
    fi
fi
