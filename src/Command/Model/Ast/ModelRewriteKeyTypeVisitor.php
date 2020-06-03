<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Command\Model\Ast;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ModelRewriteKeyTypeVisitor extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $keyType;

    /**
     * @var bool
     */
    protected $has = false;

    public function __construct(string $class, string $keyType)
    {
        $this->class = $class;
        $this->keyType = $keyType;
    }

    public function leaveNode(Node $node)
    {
        switch ($node) {
            case $node instanceof Node\Stmt\Property:
                if ($node->props[0]->name->toString() === 'keyType') {
                    $this->has = true;
                    if ($this->shouldRemoved()) {
                        return NodeTraverser::REMOVE_NODE;
                    }

                    $node->props[0]->default = new Node\Scalar\String_($this->keyType);
                }

                return $node;
        }
    }

    public function afterTraverse(array $nodes)
    {
        if ($this->has || $this->shouldRemoved()) {
            return null;
        }

        foreach ($nodes as $namespace) {
            if (!$namespace instanceof Node\Stmt\Namespace_) {
                continue;
            }
            foreach ($namespace->stmts as $class) {
                if (!$class instanceof Node\Stmt\Class_) {
                    continue;
                }
                foreach ($class->stmts as $property) {
                    $flags = Node\Stmt\Class_::MODIFIER_PROTECTED;
                    $prop = new Node\Stmt\PropertyProperty('keyType', new Node\Scalar\String_($this->keyType));
                    $class->stmts[] = new Node\Stmt\Property($flags, [$prop]);
                    return null;
                }
            }
        }

        return null;
    }

    protected function shouldRemoved(): bool
    {
        $ref = new \ReflectionClass($this->class);

        if (!$ref->getParentClass()) {
            return false;
        }

        $keyType = $ref->getParentClass()->getDefaultProperties()['keyType'] ?? 'int';
        return $keyType === $this->keyType;
    }
}
