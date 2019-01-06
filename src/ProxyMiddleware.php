<?php

namespace Woody\Middleware\Proxy;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Woody\Middleware\Proxy\Driver\DriverInterface;
use Woody\Middleware\Proxy\Model\RouteInterface;
use Woody\Middleware\Proxy\Model\ServiceInterface;
use Woody\Middleware\Proxy\Model\TargetInterface;
use Woody\Middleware\Proxy\Model\UpstreamInterface;

/**
 * Class ProxyMiddleware
 *
 * @package Woody\Middleware\Proxy
 */
class ProxyMiddleware
{

    const HEADER_VIA = 'woody';

    /**
     * @var \Woody\Middleware\Proxy\Model\ServiceInterface[]
     */
    protected $services = [];

    /**
     * @var \Woody\Middleware\Proxy\Model\UpstreamInterface[]
     */
    protected $upstreams = [];

    /**
     * @var \Woody\Middleware\Proxy\Driver\DriverInterface
     */
    protected $driver;

    /**
     * @var array
     */
    protected $headersToRemove = [
        'Connection',
        'Content-Length',
        'Keep-Alive',
        'TE',
        'Transfer-Encoding',
    ];

    /**
     * ProxyMiddleware constructor.
     *
     * @param \Woody\Middleware\Proxy\Driver\DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param \Woody\Middleware\Proxy\Model\ServiceInterface $service
     *
     * @return \Woody\Middleware\Proxy\ProxyMiddleware
     */
    public function addService(ServiceInterface $service): ProxyMiddleware
    {
        $this->services[] = $service;

        return $this;
    }

    /**
     * @param \Woody\Middleware\Proxy\Model\UpstreamInterface $upstream
     *
     * @return \Woody\Middleware\Proxy\ProxyMiddleware
     */
    public function addUpstream(UpstreamInterface $upstream): ProxyMiddleware
    {
        $this->upstreams[] = $upstream;

        return $this;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Prepare request.
        $request = $this->preHandle($request);

        // Do request to upstream.
        $response = $this->driver->handle($request);

        // Alter response.
        $response = $this->postHandle($response);

        return $response;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function preHandle(ServerRequestInterface $request): ServerRequestInterface
    {
        $route = $this->detectRoute($request);
        $target = $this->detectTarget($route, $request);

        return $this->prepareRequest($request, $route, $target);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function postHandle(ResponseInterface $response): ResponseInterface
    {
        // Remove headers.
        foreach ($this->headersToRemove as $header) {
            $response = $response->withoutHeader($header);
        }

        $via = $response->getHeaderLine('Via');
        $via .= ($via ? ', ':'').$response->getProtocolVersion().' '.static::HEADER_VIA;

        return $response->withHeader('Via', $via);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Woody\Middleware\Proxy\Model\RouteInterface
     */
    protected function detectRoute(ServerRequestInterface $request): RouteInterface
    {
        foreach ($this->services as $service) {
            foreach ($service->getRoutes() as $route) {
                if ($route->matchUri($request->getMethod(), $request->getUri())) {
                    return $route;
                }
            }
        }

        throw new \RuntimeException('Unable to detect service');
    }

    /**
     * @param \Woody\Middleware\Proxy\Model\RouteInterface $route
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Woody\Middleware\Proxy\Model\TargetInterface
     */
    protected function detectTarget(RouteInterface $route, ServerRequestInterface $request): TargetInterface
    {
        $host = $route->getService()->getHost();

        foreach ($this->upstreams as $upstream) {
            if ($host == $upstream->getName()) {
                return $upstream->detectTarget($request);
            }
        }

        throw new \RuntimeException('Unable to detect upstream');
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Woody\Middleware\Proxy\Model\RouteInterface $route
     * @param \Woody\Middleware\Proxy\Model\TargetInterface $target
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function prepareRequest(ServerRequestInterface $request, RouteInterface $route, TargetInterface $target): ServerRequestInterface
    {
        $service = $route->getService();

        // Override scheme (http / https).
        $uri = $request->getUri();
        $uri = $uri->withScheme($service->getProtocol());

        // Alter host/port.
        if (!$route->getPreserveHost()) {
            $port = $route->getService()->getPort();
            $uri = $uri->withHost($target->getHost())->withPort($target->getPort($port));
            $request = $request->withoutHeader('Host');
        }

        // Alter path by removing route prefix.
        if ($route->getStripPath()) {
            foreach ($route->getPaths() as $path) {
                if (strpos($uri->getPath(), $path) === 0) {
                    $path = substr($uri->getPath(), strlen($path)) ?: '/';
                    $uri = $uri->withPath($path);
                    break;
                }
            }
        }

        // Alter path by adding service prefix.
        if ($path = $service->getPath()) {
            $uri = $uri->withPath($path.$uri->getPath());
        }

        // Remove headers.
        foreach ($this->headersToRemove as $header) {
            $request = $request->withoutHeader($header);
        }

        return $request->withUri($uri);
    }
}
