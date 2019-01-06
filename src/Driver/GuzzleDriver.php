<?php

namespace Woody\Middleware\Proxy\Driver;

use GuzzleHttp\Client;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GuzzleDriver
 *
 * @package Woody\Middleware\Proxy\Driver
 */
class GuzzleDriver implements DriverInterface
{

    /**
     * @var array
     */
    protected $config;
    
    /**
     * GuzzleDriver constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!class_exists('\GuzzleHttp\Client')) {
            throw new \RuntimeException('Guzzle library is required');
        }
        
        $this->config = $config;
    }

    /**
     * @inheritDoc
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $client = new Client($this->config);

        return $client->send($request);
    }
}
