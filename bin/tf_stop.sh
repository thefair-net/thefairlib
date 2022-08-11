#!/usr/bin/env sh

basepath=$(cd `dirname $0`; pwd)
cd $basepath
cd ../../../../

signal=$1

if [ "${signal}" = "" ];then
    signal=15
fi

if [ -f "./runtime/hyperf.pid" ]; then
    pid=$(cat ./runtime/hyperf.pid)
    echo $pid
    if [ $signal -eq 9 ]; then
         /bin/kill -9 $pid
    else
        /bin/kill -TERM $pid
    fi
fi