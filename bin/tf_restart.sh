#!/usr/bin/env sh

basepath=$(cd `dirname $0`; pwd)
cd $basepath
cd ../../

if [ -f "./runtime/hyperf.pid" ]; then
    pid=$(cat ./runtime/hyperf.pid)
    echo $pid
    /bin/kill -TERM $pid
    sleep 4
fi

php ./bin/hyperf.php manage:start
nohup php ./bin/hyperf.php start > ./runtime/info.log 2>&1 &