<?php declare(strict_types=1);

namespace Kuria\Router\Route;

class PathPattern extends Pattern
{
    protected const RAW_CHARS = [
        '%21' => '!',
        '%2A' => '*',
        '%2B' => '+',
        '%2C' => ',',
        '%2F' => '/',
        '%3A' => ':',
        '%3B' => ';',
        '%3D' => '=',
        '%40' => '@',
        '%7C' => '|',
    ];

    protected function encodeGeneratedPart(string $part): string
    {
        // URL-encode the part, but allow some special characters
        return strtr(rawurlencode($part), static::RAW_CHARS);
    }
}
