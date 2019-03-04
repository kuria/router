<?php declare(strict_types=1);

namespace Kuria\Router\Result;

use Kuria\Router\Subject;

/**
 * No routes have matched because of the method
 */
class MethodNotAllowed extends Result
{
    /** @var string[] */
    private $allowedMethods;

    /**
     * @param string[] $allowedMethods
     */
    function __construct(Subject $subject, array $allowedMethods)
    {
        parent::__construct($subject);

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
