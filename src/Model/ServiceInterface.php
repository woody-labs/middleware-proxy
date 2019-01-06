<?php

namespace Woody\Middleware\Proxy\Model;

/**
 * Interface ServiceInterface
 *
 * @package Woody\Middleware\Proxy\Model
 */
interface ServiceInterface
{

    /**
     * The Service name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * The protocol used to communicate with the upstream.
     * It can be one of http (default) or https.
     *
     * @return string
     */
    public function getProtocol(): string;

    /**
     * The host of the upstream server.
     *
     * @return string
     */
    public function getHost(): string;

    /**
     * The upstream server port.
     * Defaults to 80.
     *
     * @return int
     */
    public function getPort(): int;

    /**
     * The path to be used in requests to the upstream server.
     * Empty by default.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * The number of retries to execute upon failure to proxy.
     * The default is 5.
     *
     * @return int
     */
    public function getRetries(): int;

    /**
     * The timeout in milliseconds for establishing
     * a connection to the upstream server.
     * Defaults to 60000.
     *
     * @return int
     */
    public function getConnectTimeout(): int;

    /**
     * The timeout in milliseconds between two successive write operations for transmitting
     * a request to the upstream server.
     * Defaults to 60000.
     *
     * @return int
     */
    public function getWriteTimeout(): int;

    /**
     * The timeout in milliseconds between two successive read operations for transmitting
     * a request to the upstream server.
     * Defaults to 60000.
     *
     * @return int
     */
    public function getReadTimeout(): int;

    /**
     * @param \Woody\Middleware\Proxy\Model\RouteInterface $route
     *
     * @return \Woody\Middleware\Proxy\Model\ServiceInterface
     */
    public function addRoute(RouteInterface $route): ServiceInterface;

    /**
     * @return \Woody\Middleware\Proxy\Model\RouteInterface[]
     */
    public function getRoutes(): array;
}
