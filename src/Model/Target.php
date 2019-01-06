<?php

namespace Woody\Middleware\Proxy\Model;

/**
 * Class Target
 *
 * @package Woody\Middleware\Proxy\Model
 */
class Target implements TargetInterface
{

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Woody\Middleware\Proxy\Model\UpstreamInterface|null
     */
    protected $upstream;

    /**
     * Target constructor.
     *
     * @param array $config
     * @param \Woody\Middleware\Proxy\Model\UpstreamInterface|null $upstream
     */
    public function __construct(array $config, UpstreamInterface $upstream = null)
    {
        $config += [
            'weight' => 100,
        ];

        if (!strpos($config['target'], ':')) {
            $config['target'] .= ':80';
        }

        if (empty($config['target'])) {
            throw new \InvalidArgumentException('Missing target value');
        }

        $this->config = $config;
        $this->upstream = $upstream;
    }

    /**
     * @inheritDoc
     */
    public function getTarget(): string
    {
        return $this->config['target'];
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        list($host,) = explode(':', $this->config['target']);

        return $host;
    }

    /**
     * @inheritDoc
     */
    public function getPort(int $defaultPort = null): int
    {
        // @todo: Call dns to detect port for svc services.
        list(, $port) = explode(':', $this->config['target']);

        return intval($port);
    }

    /**
     * @inheritDoc
     */
    public function getWeight(): int
    {
        return $this->config['weight'];
    }

    /**
     * @inheritDoc
     */
    public function setUpstream(UpstreamInterface $upstream): TargetInterface
    {
        $this->upstream = $upstream;

        return $this;
    }
}
