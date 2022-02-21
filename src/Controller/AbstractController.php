<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Controller;

use Hyperf\Context\Context;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Exception\ServiceException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use Psr\Container\ContainerInterface;

abstract class AbstractController implements \TheFairLib\Contract\ResponseInterface
{
    /**
     * @Inject
     * @var \TheFairLib\Contract\ResponseInterface
     */
    protected $serviceResponse;

    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject()
     * @var ValidatorFactoryInterface
     */
    protected $validationFactory;


    /**
     * 全局参数验证
     *
     * @param RequestInterface $request
     * @param array $rules
     * @param array $messages
     * @return array
     */
    final protected function validateParam(RequestInterface $request, array $rules, array $messages = [])
    {
        $validator = $this->validationFactory->make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator, $this->response);
        }
        return $validator->validated();
    }

    /**
     * 正常返回.
     *
     * @param $result
     * @param string $msg
     * @param int $code
     * @param string $action
     * @return array
     */
    final public function showResult(array $result, string $msg = '', int $code = 0, $action = 'toast')
    {
        return $this->serviceResponse->showResult($result, $msg, $code, $action);
    }

    /**
     * 失败返回.
     *
     * @param $msg
     * @param array $result
     * @param int $code
     * @param string $action
     * @return array
     */
    final public function showError(string $msg, array $result = [], int $code = InfoCode::CODE_ERROR, $action = 'toast')
    {
        return $this->serviceResponse->showError($msg, $result, $code, $action);
    }

    /**
     * 成功
     *
     * @param string $msg
     * @param string $action
     * @return array
     */
    final public function showSuccess(string $msg = '', $action = 'toast')
    {
        return $this->serviceResponse->showSuccess($msg, $action);
    }
}
