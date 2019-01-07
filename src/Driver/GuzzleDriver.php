<?php

namespace Woody\Middleware\Proxy\Driver;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        $config += [
            'cookies' => true,
        ];

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

        $response = $client->send($request);
        $response = $this->setCookieHeaders($client, $response);
        $cookies = $client->getConfig('cookies');

        return $response;
    }

    /**
     * @param \GuzzleHttp\ClientInterface $client
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function setCookieHeaders(ClientInterface $client, ResponseInterface $response): ResponseInterface
    {
        $cookies = $client->getConfig('cookies');

        /** @var \GuzzleHttp\Cookie\SetCookie $cookie */
        foreach ($cookies as $cookie) {
            $response = $response->withAddedHeader('Set-Cookie', (string)$cookie);
        }

        return $response;
    }
}
