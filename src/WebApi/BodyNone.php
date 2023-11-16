<?php

namespace Teleskill\Framework\WebApi;

use Teleskill\Framework\Logger\Log;
use Exception;

class BodyNone {

    const LOGGER_NS = self::class;

    private ?string $data = null;
    
    public function __construct() {
        
    }

    public function get() : array {
        return [
            'body' => $this->data
        ];
    }

    public function toString() : string|null {
        try {
            return $this->data;
        } catch (Exception $e) {
            return null;
        }
    }
}