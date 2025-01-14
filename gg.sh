#!/bin/sh

pwd_dir=$(cd $(dirname $0); pwd)
echo "Current directory: $pwd_dir"

cd "$pwd_dir"

if [ -n "$(git status --porcelain)" ]; then
    echo "Uncommitted changes detected. Discarding local changes."
    git reset --hard
fi

git fetch --all
git reset --hard origin/master

echo "Upgrade IYUU Core successfully."
