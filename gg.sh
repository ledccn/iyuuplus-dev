#!/bin/sh

pwd_dir=$(cd $(dirname $0); pwd)
echo "Current directory: $pwd_dir"

cd "$pwd_dir"

git fetch --all
git reset --hard origin/master

echo "Upgrade IYUU Core successfully."
