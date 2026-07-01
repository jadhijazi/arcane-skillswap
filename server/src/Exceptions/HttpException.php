<?php
declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

class HttpException extends \Exception
{
    private int $statusCode;

    public function __construct(string $message = '', int $statusCode = 400, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
