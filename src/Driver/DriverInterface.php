<?php

namespace Woody\Middleware\Proxy\Driver;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface DriverInterface
 *
 * @package Woody\Middleware\Proxy\Driver
 */
interface DriverInterface
{

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
