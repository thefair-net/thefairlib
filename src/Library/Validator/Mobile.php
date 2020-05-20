<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Str.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-05-12 10:36:00
 *
 **/

namespace TheFairLib\Library\Validator;

use TheFairLib\Contract\ValidatorExtendInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Request;
use Hyperf\Utils\Context;
use Hyperf\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;

class Mobile implements ValidatorExtendInterface
{

    /**
     * @var Request
     */
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function extend($attribute, $value, $parameters, $validator): bool
    {
        return (bool)preg_match('/^1[3456789][0-9]{9}$/', $value);
    }

    /**
     * 当创建一个自定义验证规则时，你可能有时候需要为错误信息定义自定义占位符这里扩展了 :foo 占位符
     *
     * @param $message
     * @param $attribute
     * @param $rule
     * @param $parameters
     * @return mixed|string|string[]
     */
    public function replacer($message, $attribute, $rule, $parameters)
    {
        return str_replace(':mobile', $attribute, $message);
    }

    public function getRuleName(): string
    {
        return 'mobile';
    }
}
