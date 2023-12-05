<?php

namespace Teleskill\Framework\WebApi;

use Teleskill\Framework\Logger\Log;
use Exception;

class RawText {

    const LOGGER_NS = self::class;

    private ?string $data = null;
    
    public function __construct() {
        
    }

    public function set(?string $data) : bool {
        try {
            $this->data = $data;

            Log::debug([self::LOGGER_NS, __FUNCTION__], 'set: ' . $data);
            
            return true;
        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], 'set exception: ' . (string) $e);

            return false;
        }
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