<?php

namespace Teleskill\Framework;

use Teleskill\Framework\ValidatorFactory;

class Validator
{
    private static ValidatorFactory $instance;

	private function __construct()
    {
        // Private constructor to prevent direct instantiation
    }

    /**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : ValidatorFactory {
		if (!isset(self::$instance)) {
            self::$instance = new ValidatorFactory();
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

		return $instance->$method(...$arguments);
    }
}