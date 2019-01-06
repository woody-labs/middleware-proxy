<?php

namespace Woody\Middleware\Proxy\Model;

use Psr\Http\Message\UriInterface;

/**
 * Class Route
 *
 * @package Woody\Middleware\Proxy\Model
 */
class Route implements RouteInterface
{
    const PROTOCOL_HTTP = 'http';

    const PROTOCOL_HTTPS = 'https';

    /**
     * @var \Woody\Middleware\Proxy\Model\ServiceInterface
     */
    protected $service;

    /**
     * @var array
     */
    protected $config;

    /**
     * Route constructor.
     *
     * @param array $config
     * @param \Woody\Middleware\Proxy\Model\ServiceInterface $service
     */
    public function __construct(array $config, ServiceInterface $service = null)
    {
        $config += [
            'protocols' => [
                static::PROTOCOL_HTTP,
                static::PROTOCOL_HTTPS,
            ],
            'methods' => [],
            'hosts' => [],
            'paths' => [],
            'regex_priority' => 0,
            'strip_path' => true,
            'preserve_host' => false,
        ];

        $this->service = $service;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getProtocols(): array
    {
        return $this->config['protocols'];
    }

    /**
     * @inheritDoc
     */
    public function getMethods(): array
    {
        return $this->config['methods'];
    }

    /**
     * @inheritDoc
     */
    public function getHosts(): array
    {
        return $this->config['hosts'];
    }

    /**
     * @inheritDoc
     */
    public function getPaths(): array
    {
        return $this->config['paths'];
    }

    /**
     * @inheritDoc
     */
    public function getRegexPriority(): int
    {
        return $this->config['regex_priority'];
    }

    /**
     * @inheritDoc
     */
    public function getStripPath(): bool
    {
        return $this->config['strip_path'];
    }

    /**
     * @inheritDoc
     */
    public function getPreserveHost(): bool
    {
        return $this->config['preserve_host'];
    }

    /**
     * @inheritDoc
     */
    public function setService(ServiceInterface $service): RouteInterface
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getService(): ServiceInterface
    {
        return $this->service;
    }

    /**
     * @inheritdoc
     */
    public function matchUri(string $method, UriInterface $uri): bool
    {
        // Match scheme.
        if ($uri->getScheme() && !in_array($uri->getScheme(), $this->config['protocols'], true)) {
            return false;
        }

        // Match methods.
        if ($this->config['methods'] && !in_array($method, $this->config['methods'], true)) {
            return false;
        }

        // Match path.
        foreach ($this->config['paths'] as $path) {
            if (strpos($uri->getPath(), $path) === 0) {
                return true;
            }
        }

        return false;
    }
}
