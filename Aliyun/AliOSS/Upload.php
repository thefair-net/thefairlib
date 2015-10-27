<?php
namespace TheFairLib\Aliyun\AliOSS;

use \TheFairLib\Aliyun\AliOSS\util\OSS_Exception as Exception;

/**
 * $file = new TheFairLib\Aliyun\AliOSS\Upload('file', [
 * "savePath" => Yaf\Registry::get('config')->cache['temp'],
 * "ossPath" => APP_NAME,
 * "maxSize" => 2000, //单位KB
 * "allowFiles" => [".gif", ".png", ".jpg", ".jpeg", ".bmp"]
 * ]);
 *
 * Class Upload
 * @package TheFairLib\Aliyun\AliOSS
 */
class Upload
{
    private $fileField;            //文件域名
    private $file;                 //文件上传对象
    private $config;               //配置信息
    private $oriName;              //原始文件名
    private $fileName;             //新文件名
    private $fullName;             //完整文件名,即从当前配置目录开始的URL
    private $newName;              //文件名
    private $ossPath;              //上传到阿里云OSS
    private $fileSize;             //文件大小
    private $fileType;             //文件类型
    private $stateInfo;            //上传状态信息,
    private $stateMap = [    //上传状态映射表，国际化用户需考虑此处数据的国际化
        "SUCCESS",                //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制",
        "文件大小超出 MAX_FILE_SIZE 限制",
        "文件未被完整上传",
        "没有文件被上传",
        "上传文件为空",
        "POST" => "文件大小超出 post_max_size 限制",
        "SIZE" => "文件大小超出网站限制",
        "TYPE" => "不允许的文件类型",
        "DIR" => "目录创建失败",
        "IO" => "输入输出错误",
        "UNKNOWN" => "未知错误",
        "MOVE" => "文件保存时出错"
    ];

    /**
     * 构造函数
     * @param string $fileField 表单名称
     * @param array $config 配置项
     * @param bool $base64 是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
     */
    public function __construct($fileField, $config, $base64 = false)
    {
        $this->fileField = $fileField;
        $this->config = $config;
        $this->stateInfo = $this->stateMap[0];
        $this->_upFile($base64);
    }

    /**
     * 上传文件的主处理方法
     * @param $base64
     * @return mixed
     */
    private function _upFile($base64)
    {
        //处理base64上传
        if ("base64" == $base64) {
            $content = $_POST[$this->fileField];
            $this->_base64ToImage($content);
            return;
        }

        //处理普通上传
        $file = $this->file = $_FILES[$this->fileField];
        if (!$file) {
            $this->stateInfo = $this->_getStateInfo('POST');
            return;
        }
        if ($this->file['error']) {
            $this->stateInfo = $this->_getStateInfo($file['error']);
            return;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->stateInfo = $this->_getStateInfo("UNKNOWN");
            return;
        }

        $this->oriName = $file['name'];
        $this->fileSize = $file['size'];
        $this->fileType = $this->_getFileExt();

        if (!$this->_checkSize()) {
            $this->stateInfo = $this->_getStateInfo("SIZE");
            return;
        }
        if (!$this->_checkType()) {
            $this->stateInfo = $this->_getStateInfo("TYPE");
            return;
        }
        $this->newName = $this->_getName();
        $this->fullName = $this->_getFolder() . '/' . $this->newName;
        $this->ossPath = $this->_getOssFolder();
        if ($this->stateInfo == $this->stateMap[0]) {
            if (!move_uploaded_file($file["tmp_name"], $this->fullName)) {
                $this->stateInfo = $this->_getStateInfo("MOVE");
            }
            $this->uploadOSS();
        }
    }

    /**
     * 上传文件到阿里云中
     *
     * @throws Exception
     */
    private function uploadOSS()
    {
        $OSS = Base::Instance();
        $state = $OSS->getALIOSSSDK()->createObjectDir($OSS->getBucketName(), $this->ossPath);//创建目录，如果存在也会返回true
        if ($state->isOK()) {
            $file = $OSS->getALIOSSSDK()->uploadFileByFile($OSS->getBucketName(), $this->ossPath . DIRECTORY_SEPARATOR . $this->newName, $this->fullName);
            $this->_rm();//删除本地文件
            if ($file->isOK()) {
                $this->ossPath = $this->config['host'] . $this->ossPath . DIRECTORY_SEPARATOR . $this->newName;
                return;
            }
            throw new Exception("上传阿里云OSS文件失败：" . json_encode($file));
        }
        throw new Exception("创建阿里云OSS目录失败：" . $this->ossPath);
    }

    /**
     * 处理base64编码的图片上传
     * @param $base64Data
     * @return mixed
     */
    private function _base64ToImage($base64Data)
    {
        $img = base64_decode($base64Data);
        $this->newName = md5(time() . rand(1, 10000)) . ".png";
        $this->fileName = $this->newName;
        $this->fullName = $this->_getFolder() . '/' . $this->fileName;
        $this->ossPath = $this->_getOssFolder();
        if (!file_put_contents($this->fullName, $img)) {
            $this->stateInfo = $this->_getStateInfo("IO");
            return;
        }
        $this->uploadOSS();
        $this->oriName = "";
        $this->fileSize = strlen($img);
        $this->fileType = ".png";
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        return [
            "originalName" => $this->oriName,
            "url" => $this->ossPath,
            "name" => $this->fileName,
            "size" => $this->fileSize,
            "type" => $this->fileType,
            "state" => $this->stateInfo
        ];
    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function _getStateInfo($errCode)
    {
        return !$this->stateMap[$errCode] ? $this->stateMap["UNKNOWN"] : $this->stateMap[$errCode];
    }

    /**
     * 重命名文件
     * @return string
     */
    private function _getName()
    {
        return $this->fileName = md5(time() . rand(1, 10000)) . $this->_getFileExt();
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function _checkType()
    {
        return in_array($this->_getFileExt(), $this->config["allowFiles"]);
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function  _checkSize()
    {
        return $this->fileSize <= ($this->config["maxSize"] * 1024);
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function _getFileExt()
    {
        return strtolower(strrchr($this->file["name"], '.'));
    }

    /**
     * 删除文件
     * @return boolean
     */
    private function _rm()
    {
        if (file_exists($this->fullName)) {
            unlink($this->fullName);
            if (is_dir($this->_getFolder())) {
                rmdir($this->_getFolder());
            }
            return true;
        }
        return false;
    }

    /**
     * 按照日期自动创建存储文件夹
     *
     * @return string
     * @throws util\OSS_Exception
     */
    private function _getOssFolder()
    {
        $pathStr = $this->config["ossPath"];
        if (strrchr($pathStr, "/") != "/") {
            $pathStr .= "/";
        }
        $pathStr .= date("Ymd");
        $OSS = Base::Instance();
        $ret = $OSS->getALIOSSSDK()->createObjectDir($OSS->getBucketName(), $pathStr);
        if ($ret->isOK()) {
            return $pathStr;
        }
        throw new Exception("自动创建存储文件夹失败：" . $pathStr);
    }

    /**
     * 按照日期自动创建存储文件夹
     * @return string
     */
    private function _getFolder()
    {
        $pathStr = $this->config["savePath"];
        if (strrchr($pathStr, "/") != "/") {
            $pathStr .= "/";
        }
        $pathStr .= date("Ymd");
        if (!file_exists($pathStr)) {
            if (!mkdir($pathStr, 0777, true)) {
                return false;
            }
        }
        return $pathStr;
    }
}
