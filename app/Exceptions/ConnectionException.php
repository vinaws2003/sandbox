<?php

namespace App\Exceptions;

use Exception;

class ConnectionException extends Exception
{
    public function __construct(
        string $message = 'Failed to connect to the node',
        int $code = 0,
        ?Exception $previous = null,
        public readonly ?string $nodeType = null,
        public readonly ?string $host = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function timeout(string $host, string $nodeType): self
    {
        return new self(
            message: "Connection to {$host} timed out",
            nodeType: $nodeType,
            host: $host,
        );
    }

    public static function refused(string $host, string $nodeType): self
    {
        return new self(
            message: "Connection to {$host} was refused",
            nodeType: $nodeType,
            host: $host,
        );
    }

    public static function authFailed(string $host, string $nodeType): self
    {
        return new self(
            message: "Authentication to {$host} failed",
            nodeType: $nodeType,
            host: $host,
        );
    }

    public static function apiError(string $host, string $nodeType, string $error): self
    {
        return new self(
            message: "API error from {$host}: {$error}",
            nodeType: $nodeType,
            host: $host,
        );
    }
}
