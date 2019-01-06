<?php

namespace Woody\Middleware\Proxy\Driver;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine\Http\Client;

/**
 * Class SwooleDriver
 *
 * @package Woody\Middleware\Proxy\Driver
 */
class SwooleDriver implements DriverInterface
{

    /**
     * @var array
     */
    protected $settings;

    /**
     * SwooleDriver constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        if (!extension_loaded('swoole')) {
            throw new \RuntimeException('Swoole php module is required');
        }

        $settings += [
            'timeout' => 30,
        ];

        $this->settings = $settings;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        if (!($port = $uri->getPort())) {
            $port = ($uri->getScheme() == 'http' ? 80 : 443);
        }

        $client = new Client($uri->getHost(), $port, $uri->getScheme() == 'https');
        $client->set($this->settings);
        $client->setMethod($request->getMethod());
        $client->setData($request->getBody()->getContents());

        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[$name] = $value;
            }
        }
        $client->setHeaders($headers);

        if (!$client->execute($request->getUri())) {
            throw new \RuntimeException($client->errMsg, $client->errCode);
        }

        return $this->prepareResponse($client);
    }

    /**
     * @param \Swoole\Coroutine\Http\Client $client
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function prepareResponse(Client $client): ResponseInterface
    {
        $response = new Response($client->statusCode ?? 500, $client->headers ?? [], $client->body);

        return $response;
    }
}
