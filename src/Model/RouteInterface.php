<?php

namespace Woody\Middleware\Proxy\Model;

use Psr\Http\Message\UriInterface;

/**
 * Interface RouteInterface
 *
 * @package Woody\Middleware\Proxy\Model
 */
interface RouteInterface
{

    /**
     * A list of the protocols this Route should allow.
     * By default it is ["http", "https"], which means
     * that the Route accepts both.
     *
     * @return array
     */
    public function getProtocols(): array;

    /**
     * A list of HTTP methods that match this Route.
     * At least one of hosts, paths, or methods must be set.
     *
     * @return array
     */
    public function getMethods(): array;

    /**
     * A list of domain names that match this Route.
     * At least one of hosts, paths, or methods must be set.
     *
     * @return array
     */
    public function getHosts(): array;

    /**
     * A list of paths that match this Route.
     * At least one of hosts, paths, or methods must be set.
     *
     * @return array
     */
    public function getPaths(): array;

    /**
     * Determines the relative order of this Route against
     * others when evaluating regex paths. Routes with higher
     * numbers will have their regex paths evaluated first.
     * Defaults to 0.
     *
     * @return int
     */
    public function getRegexPriority(): int;

    /**
     * When matching a Route via one of the paths, strip
     * the matching prefix from the upstream request URL.
     * Defaults to true.
     *
     * @return bool
     */
    public function getStripPath(): bool;

    /**
     * When matching a Route via one of the hosts domain names,
     * use the request Host header in the upstream request headers.
     * By default set to false, and the upstream Host header will
     * be that of the Service’s host.
     *
     * @return bool
     */
    public function getPreserveHost(): bool;

    /**
     * The Service this Route is associated to.
     * This is where the Route proxies traffic to.
     *
     * @param \Woody\Middleware\Proxy\Model\ServiceInterface $service
     *
     * @return \Woody\Middleware\Proxy\Model\RouteInterface
     */
    public function setService(ServiceInterface $service): RouteInterface;

    /**
     * The Service this Route is associated to.
     * This is where the Route proxies traffic to.
     *
     * @return \Woody\Middleware\Proxy\Model\ServiceInterface
     */
    public function getService(): ServiceInterface;

    /**
     * @param string $method
     * @param \Psr\Http\Message\UriInterface $uri
     *
     * @return bool
     */
    public function matchUri(string $method, UriInterface $uri): bool;
}
