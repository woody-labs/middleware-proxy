<?php

namespace Woody\Middleware\Proxy\Model;

/**
 * Class Service
 *
 * @package Woody\Middleware\Proxy\Model
 */
class Service implements ServiceInterface
{

    const PROTOCOL_HTTP = 'http';

    const PROTOCOL_HTTPS = 'https';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Woody\Middleware\Proxy\Model\RouteInterface[]
     */
    protected $routes = [];

    /**
     * Service constructor.
     *
     * @param array $config
     * @param array $routes
     */
    public function __construct(array $config, array $routes = [])
    {
        $config += [
            'name' => '',
            'path' => '',
            'retries' => 5,
            'connection_timeout' => 60000,
            'write_timeout' => 60000,
            'read_timeout' => 60000,
        ];

        if (empty($config['protocol'])) {
            throw new \InvalidArgumentException('Missing protocol value');
        }

        if (empty($config['host'])) {
            throw new \InvalidArgumentException('Missing host value');
        }

        if (empty($config['port'])) {
            throw new \InvalidArgumentException('Missing port value');
        }

        $this->config = $config;

        foreach ($routes as $route) {
            $this->addRoute($route);
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->config['name'];
    }

    /**
     * @inheritDoc
     */
    public function getProtocol(): string
    {
        return $this->config['protocol'];
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->config['host'];
    }

    /**
     * @inheritDoc
     */
    public function getPort(): int
    {
        return $this->config['port'];
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->config['path'];
    }

    /**
     * @inheritDoc
     */
    public function getRetries(): int
    {
        return $this->config['retries'];
    }

    /**
     * @inheritDoc
     */
    public function getConnectTimeout(): int
    {
        return $this->config['connect_timeout'];
    }

    /**
     * @inheritDoc
     */
    public function getWriteTimeout(): int
    {
        return $this->config['write_timeout'];
    }

    /**
     * @inheritDoc
     */
    public function getReadTimeout(): int
    {
        return $this->config['read_timeout'];
    }

    /**
     * @inheritDoc
     */
    public function addRoute(RouteInterface $route): ServiceInterface
    {
        $this->routes[] = $route->setService($this);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
