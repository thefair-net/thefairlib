<?php

declare(strict_types=1);

namespace TheFairLib\Library\Http;

use TheFairLib\Constants\InfoCode;
use TheFairLib\Contract\ResponseInterface;
use TheFairLib\Exception\ServiceException;
use Hyperf\Utils\Context;

/**
 * Class Response.
 * @property array|mixed result
 * @property string msg
 * @property int code
 * @property string action
 */
class ApiResponse implements ResponseInterface
{
    private $params = [
        'result',
        'code',
        'msg',
        'action',
    ];

    final public function __get($name)
    {
        if (!in_array($name, $this->params)) {
            throw new ServiceException('error param', ['name' => $name]);
        }
        return Context::get(__CLASS__ . ':' . $name);
    }

    final public function __set($name, $value)
    {
        if (!in_array($name, $this->params)) {
            throw new ServiceException('error param', [$name => $value]);
        }
        return Context::set(__CLASS__ . ':' . $name, $value);
    }

    /**
     * {@inheritdoc}
     */
    final public function showResult(array $result, string $msg = '', int $code = 0, string $action = 'toast')
    {
        $this->result = $result;
        $this->msg = $msg;
        $this->code = $code;
        $this->action = $action;
        return $this->toArray();
    }

    /**
     * {@inheritdoc}
     */
    final public function showError(string $error, array $result = [], int $code = InfoCode::CODE_ERROR, string $action = 'toast')
    {
        return $this->showResult($result, $error, $code, $action);
    }

    /**
     * {@inheritdoc}
     */
    final public function showSuccess(string $msg = '', string $action = 'toast')
    {
        return $this->showResult(['status' => true], $msg ?: 'success', 0, $action);
    }

    private function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => [
                'content' => $this->msg,
                'action' => $this->action,
            ],
            'result' => $this->result,
        ];
    }
}
