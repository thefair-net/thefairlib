<?php

declare(strict_types=1);

namespace TheFairLib\Contract;

interface ValidatorExtendInterface
{
    public function extend($attribute, $value, $parameters, $validator): bool;

    /**
     * 当创建一个自定义验证规则时，你可能有时候需要为错误信息定义自定义占位符这里扩展了 :foo 占位符
     *
     * @param $message
     * @param $attribute
     * @param $rule
     * @param $parameters
     * @return mixed
     */
    public function replacer($message, $attribute, $rule, $parameters);

    /**
     * 规则名称
     *
     * @return mixed
     */
    public function getRuleName(): string;
}
