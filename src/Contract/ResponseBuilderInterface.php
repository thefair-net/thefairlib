<?php

namespace TheFairLib\Contract;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ResponseBuilderInterface
{
    public function buildErrorResponse(ServerRequestInterface $request, int $code, Throwable $error = null): ResponseInterface;

    public function buildResponse(ServerRequestInterface $request, $response): ResponseInterface;
}
