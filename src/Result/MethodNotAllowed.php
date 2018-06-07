<?php declare(strict_types=1);

namespace Kuria\Router\Result;

/**
 * No routes have matched because of the method
 */
class MethodNotAllowed implements ResultInterface
{
    /** @var string[] */
    private $allowedMethods;

    /**
     * @param string[] $allowedMethods
     */
    function __construct(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @return string[]
     */
    function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
