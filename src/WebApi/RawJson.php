<?php

namespace Teleskill\Framework\WebApi;

use Teleskill\Framework\Logger\Log;
use Exception;

class RawJson {

    const LOGGER_NS = self::class;

    private ?array $data = null;
    
    public function __construct() {
        
    }

    public function set(?array $data) : bool {
        try {
            $this->data = $data;

            Log::debug([self::LOGGER_NS, __FUNCTION__], 'set: ' . json_encode($data));
            
            return true;
        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], 'set exception: ' . (string) $e);

            return false;
        }
    }

    public function get() : array {
        return [
            'json' => $this->data
        ];
    }

    public function toString() : string|null {
        try {
            return json_encode($this->data);
        } catch (Exception $e) {
            return null;
        }
    }
}