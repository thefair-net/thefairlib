<?php

namespace TheFairLib\Library\Http;

use Hyperf\Contract\ContainerInterface;
use Hyperf\JsonRpc\DataFormatter;
use Hyperf\JsonRpc\Packer\JsonLengthPacker;
use Hyperf\JsonRpc\ResponseBuilder;

/**
 * Class ResponseBuilderFactory
 * @package TheFairLib\Library\Http
 */
class ResponseBuilderFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return make(ResponseBuilder::class, [
            'dataFormatter' => $container->get(DataFormatter::class),
            'packer' => $container->get(JsonLengthPacker::class),
        ]);
    }
}
