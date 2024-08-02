<?php

namespace JobSearcher\Exception\Extraction;

use Exception;
use Throwable;

class TerminateProcessException extends Exception
{
    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param Throwable $e
     *
     * @return self
     */
    public static function fromException(Throwable $e): self
    {
        $msg = "Original Message: {$e->getMessage()}. Trace: {$e->getTraceAsString()}";

        return new TerminateProcessException($msg, $e->getCode());
    }
}