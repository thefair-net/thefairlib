#!/usr/bin/env sh

basepath=$(cd `dirname $0`; pwd)
cd $basepath
cd ../../

app_path=$(pwd)

echo $app_path

if [ ! -f "composer.lock" ]; then
  echo "Not found composer.lock, please composer install first."
  exit
fi
echo "[start] git pull"
git pull
echo "[start] init cache"

# 配置环境变量，这一步非常重要
export COMPOSER_HOME=$app_path

if ! which composer; then
  echo "Not found composer. install path /usr/local/bin/composer"
  exit
fi

echo "[start] manage:stop"
/usr/bin/php ./bin/hyperf.php manage:stop
echo "[start] manage:stop"

# systemctl 里面没有找具体的项目目录，必须指定路径
/usr/local/bin/composer dump-autoload -o -d $app_path -q

echo "[start] init cache success"

echo "[start] unit test start..."
if ! /usr/local/bin/composer test -d $app_path -q; then
    echo "unit test fail..."
    echo "unit test fail..."
    echo "unit test fail..."
    exit
fi
echo "[start] unit test success..."

echo "[start] service stop"

if [ -f "./runtime/hyperf.pid" ]; then
    pid=$(cat ./runtime/hyperf.pid)
    echo $pid
    /bin/kill -TERM $pid
fi