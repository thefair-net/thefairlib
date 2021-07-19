#!/usr/bin/env sh

basepath=$(cd `dirname $0`; pwd)
cd $basepath
cd ../../

/usr/bin/php ./bin/hyperf.php manage:start
nohup /usr/bin/php ./bin/hyperf.php start > ./runtime/info.log 2>&1 &