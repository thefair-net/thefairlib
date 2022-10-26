#!/usr/bin/env sh

basepath=$(cd `dirname $0`; pwd)
cd $basepath
cd ../../../../
# 开启之后，第一步上线节点
php ./bin/hyperf.php manage:start
nohup php ./bin/hyperf.php start > ./runtime/info.log 2>&1 &
# 开启之后，第二步上线流量
php ./bin/hyperf.php manage:start nacos