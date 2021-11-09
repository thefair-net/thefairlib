#!/usr/bin/env sh

basepath=$(cd `dirname $0`; pwd)
cd $basepath
cd ../../

nohup php ./bin/hyperf.php start > ./runtime/info.log 2>&1 &
# 开启之后，再上线流量
php ./bin/hyperf.php manage:start