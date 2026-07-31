<?php

namespace App\Messaging;

class HmacSigner
{
    public function bodyHash(string $body): string
    {
        return hash('sha256', $body);
    }

    public function canonical(string $method, string $path, string $timestamp, string $requestId, string $body): string
    {
        return mb_strtoupper($method)."\n".$path."\n".$timestamp."\n".$requestId."\n".$this->bodyHash($body);
    }

    public function sign(string $method, string $path, string $timestamp, string $requestId, string $body, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $this->canonical($method, $path, $timestamp, $requestId, $body), $secret);
    }
}
