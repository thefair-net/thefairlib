#!/usr/bin/env sh

basepath=$(cd `dirname $0`; pwd)
cd $basepath
cd ../
echo $(pwd)

if [ ! -f "composer.lock" ]; then
  echo "Not found composer.lock, please composer install first."
  exit
fi

rm -rf runtime/container

echo "Runtime cleared"

php bin/hyperf.php di:init-proxy

echo "Finish!"

