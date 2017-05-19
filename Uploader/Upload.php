<?php
namespace TheFairLib\Uploader;
    /**
     * Class Upload
     * @package Uploader
     */
/*
$file = new TheFairLib\Uploader\Upload('files', array(
    "savePath" => Registry::get('config')->cache['temp'],
    "maxSize" => 2000, //单位KB
    "allowFiles" => array(".gif", ".png", ".jpg", ".jpeg", ".bmp")
));
$status = $file->getFileInfo();
 */

class Upload
{
    private $fileField;            //文件域名
    private $file;                 //文件上传对象
    private $config;               //配置信息
    private $oriName;              //原始文件名
    private $fileName;             //新文件名
    private $fullName;             //完整文件名,即从当前配置目录开始的URL
    private $fileSize;             //文件大小
    private $fileType;             //文件类型
    private $stateInfo;            //上传状态信息,
    private $stateMap = array(    //上传状态映射表，国际化用户需考虑此处数据的国际化
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
    );

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
            $this->_base64ToImage($this->fileField);
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
        $this->fullName = $this->_getFolder() . '/' . $this->_getName();
        if ($this->stateInfo == $this->stateMap[0]) {
            if (!move_uploaded_file($file["tmp_name"], $this->fullName)) {
                $this->stateInfo = $this->_getStateInfo("MOVE");
            }
        }
    }

    /**
     * 处理base64编码的图片上传
     * @param $base64Data
     * @return mixed
     */
    private function _base64ToImage($base64Data)
    {
        $img = base64_decode($base64Data);
        $this->fileName = time() . rand(1, 10000) . ".png";
        $this->fullName = $this->_getFolder() . '/' . $this->fileName;
        if (!file_put_contents($this->fullName, $img)) {
            $this->stateInfo = $this->_getStateInfo("IO");
            return;
        }
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
            "name" => $this->fileName,
            "url" => $this->fullName,
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
        return $this->fileName = time() . rand(1, 10000) . $this->_getFileExt();
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