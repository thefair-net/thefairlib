<?php

declare(strict_types=1);

namespace TheFairLib\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Doc extends AbstractAnnotation
{

    /**
     * 名称
     *
     * @var string
     */
    public $name = '';

    /**
     * 文档描述
     *
     * @var string
     */
    public $desc = '';

    /**
     * 预留字段
     *
     * @var array
     */
    public $tag = [];
}
