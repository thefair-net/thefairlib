<?php
declare(strict_types=1);

namespace TheFairLib\Library\Logger;

use Psr\Container\ContainerInterface;

class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return Logger::get();
    }
}
