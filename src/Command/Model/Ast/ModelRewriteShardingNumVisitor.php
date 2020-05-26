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
use ReflectionClass;
use ReflectionException;

class ModelRewriteShardingNumVisitor extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var int
     */
    protected $shardingNum;

    /**
     * @var bool
     */
    protected $has = false;

    public function __construct(string $class, int $shardingNum)
    {
        $this->class = $class;
        $this->shardingNum = $shardingNum;
    }

    public function leaveNode(Node $node)
    {
        switch ($node) {
            case $node instanceof Node\Stmt\Property:
                if ($node->props[0]->name->toString() === 'shardingNum') {
                    $this->has = true;
                    try {
                        if ($this->shouldRemovedConnection()) {
                            return NodeTraverser::REMOVE_NODE;
                        }
                    } catch (ReflectionException $e) {
                        throw $e;
                    }

                    $node->props[0]->default = new Node\Scalar\LNumber($this->shardingNum);
                }

                return $node;
        }
    }

    /**
     * @param array $nodes
     * @return Node[]|null
     * @throws ReflectionException
     */
    public function afterTraverse(array $nodes)
    {
        if ($this->has || $this->shouldRemovedConnection()) {
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
                    $prop = new Node\Stmt\PropertyProperty('shardingNum', new Node\Scalar\LNumber($this->shardingNum));
                    $class->stmts[] = new Node\Stmt\Property($flags, [$prop]);
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    protected function shouldRemovedConnection(): bool
    {
        $ref = new ReflectionClass($this->class);

        if (!$ref->getParentClass()) {
            return false;
        }

        $shardingNum = $ref->getParentClass()->getDefaultProperties()['shardingNum'] ?? 0;
        return $shardingNum === $this->shardingNum;
    }
}
