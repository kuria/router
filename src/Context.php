<?php declare(strict_types=1);

namespace Kuria\Router;

class Context
{
    /** @var string */
    public $scheme;

    /** @var string */
    public $host;

    /** @var int */
    public $port;

    /** @var string */
    public $basePath;

    /** @var int */
    private $httpPort;

    /** @var int */
    private $httpsPort;

    function __construct(
        string $scheme,
        string $host,
        int $port,
        string $basePath,
        ?int $httpPort = null,
        ?int $httpsPort = null
    ) {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->basePath = $basePath;
        $this->httpPort = $httpPort ?? ($scheme === 'https' ? 80 : $port);
        $this->httpsPort = $httpsPort ?? ($scheme === 'https' ? $port : 443);
    }

    function getPortForScheme(string $scheme): int
    {
        return $scheme === 'https' ? $this->httpsPort : $this->httpPort;
    }
}
