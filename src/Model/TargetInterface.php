<?php

namespace Woody\Middleware\Proxy\Model;

/**
 * Interface TargetInterface
 *
 * @package Woody\Middleware\Proxy\Model
 */
interface TargetInterface
{

    /**
     * The target address (ip or hostname) and port.
     * If omitted the port defaults to 80.
     * If the hostname resolves to an SRV record, the port value will overridden by the value from the dns record.
     *
     * @return string
     */
    public function getTarget(): string;

    /**
     * The weight this target gets within the upstream load balance (0-1000, defaults to 100).
     * If the hostname resolves to an SRV record, the weight value will
     * overridden by the value from the dns record.
     *
     * @return int
     */
    public function getWeight(): int;

    /**
     * Dynamic method.
     *
     * @return string
     */
    public function getHost(): string;

    /**
     * Dynamic method.
     *
     * @param int|null $defaultPort
     *
     * @return int
     */
    public function getPort(int $defaultPort = null): int;

    /**
     * @param \Woody\Middleware\Proxy\Model\UpstreamInterface $upstream
     *
     * @return \Woody\Middleware\Proxy\Model\TargetInterface
     */
    public function setUpstream(UpstreamInterface $upstream): TargetInterface;
}
