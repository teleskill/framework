<?php

namespace Teleskill\Framework\Database;

use Teleskill\Framework\Database\CapsuleManager;

class Eloquent {

    public function __construct(string $id, array $settings) {
        CapsuleManager::addConnection($settings, $id);
    }
}