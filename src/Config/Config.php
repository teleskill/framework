<?php

namespace Teleskill\Framework\Config;

use Teleskill\Framework\Core\App;
use Teleskill\Framework\Config\Enums\Environment;

class Config {

    private static Config $instance;
    private array $data;
    private ?Environment $environment;

    public function __construct() {
        
    }

    public static function getInstance() : Config {
        if (!isset(self::$instance)) {
            $basePath = App::basePath();

            self::$instance = new Config();

            self::$instance->environment = Environment::tryFrom(include($basePath . 'config.php')) ?? null;

            self::$instance->data = include($basePath . 'config_' . self::$instance->environment->value . '.php');
        }

        return self::$instance;
    }

    public static function get(string $item, string $key = null) : mixed {
        $instance = self::getInstance();

        if ($key) {
            return $instance->data[$item][$key] ?? null;
        } else {
            return $instance->data[$item] ?? null;
        }
    }

    public static function environment() : Environment|null {
        $instance = self::getInstance();

        return $instance->environment ?? null;
    }

    public static function settings() : Config {
        return self::getInstance();
    }

}
