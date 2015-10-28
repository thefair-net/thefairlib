### 阿里云OSS上传

**config目录下新建AliYun.php文件，不能使用其他名称**

```
<?php

/**
 * 阿里云CDN配置文件
 *
 * @author mingzhil
 * @mail liumingzhij26@qq.com
 */
namespace config;

class AliYun
{
    /**
     * 只允许修改参数，其他不能改变
     */
    public $OSS = [
        'OSS_ACCESS_ID' => 'ouPEG3yPIkwCrJFZ',
        'OSS_ACCESS_KEY' => 'c4zRsWNIQiyTueNnxxudxt4BMjR93t',
        'OSS_ENDPOINT' => 'oss-cn-beijing.aliyuncs.com',
        'OSS_TEST_BUCKET' => 'static-pub',
        'ALI_LOG' => false,
        'ALI_DISPLAY_LOG' => false,
        'ALI_LANG' => 'zh',
    ];
}

```



**Demo**

```
$file = new TheFairLib\Aliyun\AliOSS\Upload('file', [
    "host" => 'http://static.biyeyuan.com/',//CDN的域名、、
    "savePath" => '/tmp',//上传文件的路径
    "ossPath" => APP_NAME,//项目名称，也就是自定义阿里云目录
    "maxSize" => 2000, //单位KB
    "allowFiles" => [".gif", ".png", ".jpg", ".jpeg", ".bmp", ".css", ".js"]
]);
$data = $file->getFileInfo();
\Response\Response::Json($data);

```

### 验证码使用

**font目录**

* 只需要将字体放到font目录下，使用数字顺序命名，即可
* 默认随机字体

**Demo**

输出验证码

```
$code = new \TheFairLib\Verify\Image();
$code->type = 'code';//类型，如login,reg,bind
$code->output(1);

```
查看或验证

```
$code = new \TheFairLib\Verify\Image();
$code->type = 'code';
$code->validate($_GET['code']);
echo $code->getCode();
```

