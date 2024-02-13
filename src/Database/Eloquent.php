<?php

namespace Teleskill\Framework\Database;

class Eloquent {

    public function __construct(string $id, array $params) {
        $capsule = new \Illuminate\Database\Capsule\Manager;
        $capsule->addConnection([]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
