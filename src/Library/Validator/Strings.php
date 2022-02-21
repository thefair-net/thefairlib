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
use Hyperf\Context\Context;
use Hyperf\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;

class Strings implements ValidatorExtendInterface
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
        if (!is_string($value)) {
            return false;
        }
        /**
         * @var Validator $validator
         */
        $data = $this->request->getParsedBody();
        $data[$attribute] = htmlspecialchars($value);

        Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) use ($data) {
            return $request->withParsedBody($data);
        });
        $value = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8', false);
        $value = mb_check_encoding($value, 'UTF-8') ? $value : utf8_encode($value);

        Context::set(RequestInterface::class . ':params:' . $attribute, $value);
        return true;
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
        return str_replace(':s', $attribute, $message);
    }

    public function getRuleName(): string
    {
        return 's';
    }
}
