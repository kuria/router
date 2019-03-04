<?php declare(strict_types=1);

namespace Kuria\Router\Result;

use Kuria\Router\Subject;

abstract class Result
{
    /** @var Subject */
    private $subject;

    function __construct(Subject $subject)
    {
        $this->subject = $subject;
    }

    function getSubject(): Subject
    {
        return $this->subject;
    }
}
