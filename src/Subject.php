<?php declare(strict_types=1);

namespace Kuria\Router;

class Subject
{
    /** @var string */
    public $method;

    /** @var string|null */
    public $scheme;

    /** @var string|null */
    public $host;

    /** @var int|null */
    public $port;

    /** @var string */
    public $path;

    /** @var array */
    public $query;

    function __construct(string $method, ?string $scheme, ?string $host, ?int $port, string $path, array $query)
    {
        $this->method = $method;
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
    }
}
