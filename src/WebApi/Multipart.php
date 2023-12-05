<?php

namespace Teleskill\Framework\WebApi;

use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Storage\LocalDisk;
use Exception;

class Multipart {

    const LOGGER_NS = self::class;

    private array $data = [];
    private array $_data = [];
    
    public function __construct() {
        
    }

    public function addField(string $field, mixed $value) : bool {
        try {
            Log::debug([self::LOGGER_NS, __FUNCTION__], 'addField: ' . $field);

            $this->data[] = [
                'name'     => $field,
                'contents' => $value
            ];

            $this->_data[] = [
                'name'     => $field
            ];

            return true;
        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], 'addField exception: ' . (string) $e);

            return false;
        }
    }

    public function addFile(string $field, LocalDisk $storage, string $fileName) : bool {
        try {
            if ($storage->fileExists($fileName)) {
                Log::debug([self::LOGGER_NS, __FUNCTION__], 'addFile: ' . $storage->getFullPathName($fileName));

                $this->data[] = [
                    'name'     => $field,
                    'contents' => $storage->read($fileName),
                    'filename' => basename($fileName)
                ];

                $this->_data[] = [
                    'name'     => $field,
                    'filename' => basename($fileName)
                ];

                return true;
            } else {
                Log::error([self::LOGGER_NS, __FUNCTION__], 'addFile not found: ' . $fileName);

                return false;
            }
        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], 'addFile exception: ' . (string) $e);

            return false;
        }
    }

    public function get() : array {
        return [
            'multipart' => $this->data
        ];
    }

    public function toString() : string|null {
        try {
            return json_encode($this->_data);
        } catch (Exception $e) {
            return null;
        }
    }
}