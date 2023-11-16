<?php

namespace Teleskill\Framework\Core;

use Teleskill\Framework\Config\Config;
use Teleskill\Framework\DateTime\CarbonDateTime;
use Teleskill\Framework\Config\Enums\Environment;

class App {

    public string $id;
    public string $url;
    public string $timezone;
    public string $basePath;
	public Environment $env;
    protected static App $instance;

    /**
	* Get Instance
	*
	* @return Singleton
	*/
	public static function getInstance() : App {
        if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();
		}

        return self::$instance;
	}

	/**
	* Avoid clone instance
	*/
	public function __clone() {
	}

	/**
	* Avoid serialize instance
	*/
	public function __sleep() {
	}

	/**
	* Avoid unserialize instance
	*/
	public function __wakeup() {
	}

    public static function id() : null|string {
        $instance = self::getInstance();

        return $instance->id;
    }

    public static function url() : null|string {
        $instance = self::getInstance();

        return $instance->url;
    }

    public static function timezone() : null|string {
        $instance = self::getInstance();

        return $instance->timezone;
    }

    public static function now() : CarbonDateTime {
        $instance = self::getInstance();

		return CarbonDateTime::now($instance->timezone);
	}

    public static function timestamp() : string {
        $instance = self::getInstance();

		return CarbonDateTime::now($instance->timezone)->timestamp;
	}

    public static function setBasePath(string $basePath) : void {
        $instance = self::getInstance();

        $instance->basePath = $basePath;

        $config = Config::get('framework', 'app');
        self::$instance->id = $config['id'];
        self::$instance->url = $config['url'];
        self::$instance->timezone = $config['timezone'] ?? 'UTC';
		self::$instance->env = Environment::tryFrom($config['env'] ?? Environment::DEV->value);
    }

    public static function basePath() : null|string {
        $instance = self::getInstance();

        return $instance->basePath;
    }

	public static function getEnv() : Environment {
		$instance = self::getInstance();
		
        return $instance->env; 
	}

    public static function stringToDateTime(?string $date, string $inputTimezone, string $inputFormat) : CarbonDateTime|null {
		$instance = self::getInstance();

		return CarbonDateTime::stringToDateTime($date, $inputTimezone, $inputFormat, $instance->timezone);
	}

	public static function stringToDate(?string $date, string $inputFormat) : CarbonDateTime|null {
		$instance = self::getInstance();

		return CarbonDateTime::stringToDate($date, $inputFormat, $instance->timezone);
	}

	public static function dateTimeToString(?CarbonDateTime $date, string $outputTimezone, string $outputFormat) : string|null {
		return CarbonDateTime::dateTimeToString($date, $outputTimezone, $outputFormat);
	}

	public static function dateToString(?CarbonDateTime $date, string $outputFormat) : string|null {
		return CarbonDateTime::dateToString($date, $outputFormat);
	}
}