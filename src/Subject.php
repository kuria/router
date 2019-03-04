<?php declare(strict_types=1);

namespace Kuria\Router;

class Subject
{
    /** @var string */
    public $method;

    /** @var string */
    public $scheme;

    /** @var string */
    public $host;

    /** @var int */
    public $port;

    /** @var string */
    public $path;

    function __construct(string $method, string $scheme, string $host, int $port, string $path)
    {
        $this->method = $method;
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
    }
}
