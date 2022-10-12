<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Exception;

use Exception;

class InvalidResponseException extends Exception implements ExceptionInterface
{
    public function __construct($message, $code = 0) {
        parent::__construct($message, $code);
    }
}