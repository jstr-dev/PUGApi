<?php

namespace App\Exceptions;

use Exception;

class BotAPIException extends Exception
{
    protected string $apiErrorCode = '';

    public function __construct(string $message = "", string $apiErrorCode = '')
    {
        $this->apiErrorCode = $apiErrorCode;
        parent::__construct($message);
    }

    public function getApiErrorCode(): string
    {
        return $this->apiErrorCode;
    }
}
