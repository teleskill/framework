<?php

namespace Teleskill\Framework\Database;

use Illuminate\Database\Capsule\Manager As Capsule;

class CapsuleManager {

	const LOGGER_NS = self::class;
	
	protected ?string $default = null;
    private static CapsuleManager $instance;
	private static Capsule $capsule;

    /**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : CapsuleManager {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();

			self::$instance->capsule = new Capsule();
            self::$instance->capsule->setAsGlobal();
            self::$instance->capsule->bootEloquent();
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

    public static function __callStatic(string $method, array $arguments) {
		$instance = self::getInstance();

		return $instance->capsule->$method(...$arguments);
    }

}
