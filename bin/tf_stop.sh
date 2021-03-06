#!/usr/bin/env sh

basepath=$(cd `dirname $0`; pwd)
cd $basepath
cd ../../

if [ -f "./runtime/hyperf.pid" ]; then
    pid=$(cat ./runtime/hyperf.pid)
    echo $pid
    /bin/kill -TERM $pid
fi