<?php

namespace Woody\Middleware\Proxy\Model;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface UpstreamInterface
 *
 * @package Woody\Middleware\Proxy\Model
 */
interface UpstreamInterface
{

    /**
     * This is a hostname, which must be equal to the host of a Service.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * The number of slots in the load balance algorithm (10-65536, defaults to 1000).
     *
     * @return int
     */
    public function getSlots(): int;

    /**
     * What to use as hashing input: none, consumer, ip, header, or cookie
     * (defaults to none resulting in a weighted-round-robin scheme).
     *
     * @return string
     */
    public function getHashOn(): string;

    /**
     * What to use as hashing input if the primary hash_on does not return a hash
     * (eg. header is missing, or no consumer identified).
     * One of: none, consumer, ip, header, or cookie (defaults to none, not available if hash_on is set to cookie).
     *
     * @return string
     */
    public function getHashFallback(): string;

    /**
     * The header name to take the value from as hash input
     * (only required when hash_on is set to header).
     *
     * @return string
     */
    public function getHashOnHeader(): string;

    /**
     * The header name to take the value from as hash input
     * (only required when hash_fallback is set to header).
     *
     * @return string
     */
    public function getHashFallbackHeader(): string;

    /**
     * The cookie name to take the value from as hash input
     * (only required when hash_on or hash_fallback is set to cookie).
     * If missing, a cookie needs to be generated.
     *
     * @return string
     */
    public function getHashOnCookie(): string;

    /**
     * The cookie path to set in the response headers
     * (only required when hash_on or hash_fallback is set to cookie, defaults to "/")
     *
     * @return string
     */
    public function getHashOnCookiePath(): string;

    /**
     * @param \Woody\Middleware\Proxy\Model\TargetInterface $target
     *
     * @return \Woody\Middleware\Proxy\Model\UpstreamInterface
     */
    public function addTarget(TargetInterface $target): UpstreamInterface;

    /**
     * @return \Woody\Middleware\Proxy\Model\TargetInterface[]
     */
    public function getTargets(): array;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Woody\Middleware\Proxy\Model\TargetInterface
     */
    public function detectTarget(ServerRequestInterface $request): TargetInterface;
}
