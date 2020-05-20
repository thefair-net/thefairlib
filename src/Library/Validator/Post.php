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
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Request;

class Post implements ValidatorExtendInterface
{

    /**
     * @Inject
     * @var Request
     */
    private $request;

    public function extend($attribute, $value, $parameters, $validator): bool
    {
        return $this->request->isMethod('post') && $this->request->post($attribute);
    }

    public function replacer($message, $attribute, $rule, $parameters)
    {
        return str_replace(':post', $attribute, $message);
    }

    public function getRuleName(): string
    {
        return 'post';
    }
}
