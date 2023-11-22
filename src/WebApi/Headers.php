<?php

namespace Teleskill\Framework\WebApi;

class Headers {

    private ?array $headers = null;
    
    public function __construct(?array $headers = null) {
        $this->headers = $headers;
    }

    public function set(?array $headers) {
        $this->headers = $headers;
    }

    public function add(string $key, mixed $value) : bool {
        $this->headers[$key] = $value;

        return true;
    }

    public function get() : array {
        return [
            'headers' => $this->headers
        ];
    }

}