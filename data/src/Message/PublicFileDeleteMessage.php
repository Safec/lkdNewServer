<?php

namespace App\Message;

class PublicFileDeleteMessage
{
    public function __construct(
        private readonly string $path,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }
}