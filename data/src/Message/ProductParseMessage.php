<?php

namespace App\Message;

class ProductParseMessage
{
    public function __construct(
        private readonly string $url,
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}