<?php

namespace Woody\Middleware\Proxy;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Woody\Http\Server\Middleware\MiddlewareInterface;
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
class ProxyMiddleware implements MiddlewareInterface
{

    const HEADER_VIA = 'woody';

    /**
     * @var \Woody\Middleware\Proxy\Driver\DriverInterface
     */
    protected $driver;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Woody\Middleware\Proxy\Model\ServiceInterface[]
     */
    protected $services = [];

    /**
     * @var \Woody\Middleware\Proxy\Model\UpstreamInterface[]
     */
    protected $upstreams = [];

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
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(DriverInterface $driver, LoggerInterface $logger)
    {
        $this->driver = $driver;
        $this->logger = $logger;
    }

    /**
     * @param bool $debug
     *
     * @return bool
     */
    public function isEnabled(bool $debug): bool
    {
        return true;
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
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Prepare request.
        $this->logger->debug('original: '.$request->getUri()->getPath(), ['correlation-id' => $request->getAttribute('correlation-id')]);
        $request = $this->preHandle($request);
        $this->logger->debug('upstream: '.$request->getUri()->getPath(), ['correlation-id' => $request->getAttribute('correlation-id')]);

        // Do request to upstream.
        $start = microtime(true);
        $response = $this->driver->handle($request);
        $duration = microtime(true) - $start;
        $this->logger->debug('duration: '.round($duration*1000).'ms', ['correlation-id' => $request->getAttribute('correlation-id')]);

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

        // @todo: use woody http exception
        throw new \RuntimeException('No Service found with those values');
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

        // @todo: use woody http exception
        throw new \RuntimeException('No Upstream found with those values');
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
        $path = $uri->getPath();

        if ($route->getStripPath()) {
            foreach ($route->getPaths() as $prefix) {
                if (strpos($path, $prefix) === 0) {
                    $path = substr($path, strlen($prefix)) ?: '';
                    break;
                }
            }
        }

        // Alter path by adding service prefix.
        if ($prefix = $service->getPath()) {
            $path = $prefix.$path;
        }
        $uri = $uri->withPath($path);

        // Remove headers.
        foreach ($this->headersToRemove as $header) {
            $request = $request->withoutHeader($header);
        }

        return $request->withUri($uri);
    }
}
