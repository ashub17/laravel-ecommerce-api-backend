<?php

namespace App\Payments\Exceptions;

use RuntimeException;

class InvalidWebhookSignatureException extends RuntimeException
{
    public function __construct(string $message = 'Webhook signature verification failed.')
    {
        parent::__construct($message);
    }
}
