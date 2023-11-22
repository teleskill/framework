<?php

namespace Teleskill\Framework\WebApi;

use Teleskill\Framework\Logger\Log;
use Exception;

class FormParams {

    const LOGGER_NS = self::class;

    private array $data = [];
    
    public function __construct() {
        
    }

    public function addField(string $field, mixed $value) : bool {
        try {
            $this->data[$field] = $value;

            Log::debug([self::LOGGER_NS, __FUNCTION__], 'addField: ' . $field);

            return true;
        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], 'addField exception: ' . Log::format($e));

            return false;
        }
    }

    public function get() : array {
        return [
            'form_params' => $this->data
        ];
    }

    public function toString() : string {
        try {
            return json_encode($this->data);
        } catch (Exception $e) {
            return null;
        }
    }
}