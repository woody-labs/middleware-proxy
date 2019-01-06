<?php

namespace Woody\Middleware\Proxy\Model;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Upstream
 *
 * @package Woody\Middleware\Proxy\Model
 */
class Upstream implements UpstreamInterface
{

    const HASH_NONE = 'none';

    const HASH_IP = 'ip';

    const HASH_HEADER = 'header';

    const HASH_COOKIE = 'cookie';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Woody\Middleware\Proxy\Model\TargetInterface
     */
    protected $targets = [];

    /**
     * Upstream constructor.
     *
     * @param array $config
     * @param array $targets
     */
    public function __construct(array $config, array $targets = [])
    {
        $config += [
            'slots' => 10000,
            'hash_on' => static::HASH_NONE,
            'hash_fallback' => static::HASH_NONE,
            'hash_on_header' => '',
            'hash_fallback_header' => '',
            'hash_on_cookie' => '',
            'hash_on_cookie_path' => '/',
        ];

        if (empty($config['name'])) {
            throw new \InvalidArgumentException('Missing name value');
        }

        $this->config = $config;
        $this->targets = $targets;
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
    public function getSlots(): int
    {
        return $this->config['slots'];
    }

    /**
     * @inheritDoc
     */
    public function getHashOn(): string
    {
        return $this->config['hash_on'];
    }

    /**
     * @inheritDoc
     */
    public function getHashFallback(): string
    {
        return $this->config['hash_fallback'];
    }

    /**
     * @inheritDoc
     */
    public function getHashOnHeader(): string
    {
        return $this->config['hash_on_header'];
    }

    /**
     * @inheritDoc
     */
    public function getHashFallbackHeader(): string
    {
        return $this->config['hash_fallback_header'];
    }

    /**
     * @inheritDoc
     */
    public function getHashOnCookie(): string
    {
        return $this->config['hash_on_cookie'];
    }

    /**
     * @inheritDoc
     */
    public function getHashOnCookiePath(): string
    {
        return $this->config['cookie_path'];
    }

    /**
     * @param \Woody\Middleware\Proxy\Model\TargetInterface $target
     *
     * @return \Woody\Middleware\Proxy\Model\UpstreamInterface
     */
    public function addTarget(TargetInterface $target): UpstreamInterface
    {
        $this->targets[] = $target->setUpstream($this);

        return $this;
    }

    /**
     * @return \Woody\Middleware\Proxy\Model\TargetInterface[]
     */
    public function getTargets(): array
    {
        return $this->targets;
    }

    /**
     * @inheritDoc
     */
    public function detectTarget(ServerRequestInterface $request): TargetInterface
    {
        switch ($this->config['hash_on']) {
            case static::HASH_NONE:
                shuffle($this->targets);

                return reset($this->targets);

            case static::HASH_IP:
                $server = array_change_key_case($request->getServerParams(), CASE_LOWER);
                $ip = ip2long($server['remote_addr']);
                $modulo = ($ip % count($this->targets));

                return $this->targets[$modulo];

            default:
                throw new \InvalidArgumentException('Target hash not supported yet');
        }
    }
}
