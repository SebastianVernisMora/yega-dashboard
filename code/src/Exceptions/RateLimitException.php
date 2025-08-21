<?php

namespace Yega\GitHub\Exceptions;

class RateLimitException extends GitHubApiException
{
    private int $resetTime;
    private int $remaining;

    public function __construct(
        string $message = '',
        int $code = 429,
        ?\Throwable $previous = null,
        int $resetTime = 0,
        int $remaining = 0
    ) {
        parent::__construct($message, $code, $previous);
        $this->resetTime = $resetTime;
        $this->remaining = $remaining;
    }

    public function getResetTime(): int
    {
        return $this->resetTime;
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getWaitTime(): int
    {
        return max(0, $this->resetTime - time());
    }
}