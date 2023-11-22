<?php

namespace Teleskill\Framework\OpenID;

use Teleskill\Framework\OpenID\Client;
use Teleskill\Framework\Config\Config;

class OpenID {

    protected ?string $default = null;
    protected array $list = [];
    protected array $connections = [];
    private static OpenID $instance;

    /**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : OpenID {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();

			$config = Config::get('framework', 'openID') ?? null;

			if ($config) {
				self::$instance->default = $config['default'];
				self::$instance->list = $config['endpoints'];
			}
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

		$conn = $instance->getConn();

		if ($conn) {
			return $conn->$method(...$arguments);
		}
        
		return null;
    }

    public static function list() : array {
		$instance = self::getInstance();

		return $instance->list;
    }

    public static function conn(string $id) : Client|null {
		$instance = self::getInstance();

		return $instance->getConn($id);
    }

    protected function getConn(?string $id = null) : Client|null {
		if (!$id) {
			$id = $this->default;
		}

		if (!isset($this->connections[$id])) {
			if (isset($this->list[$id])) {
				$params = $this->list[$id];

				$this->connections[$id] = new Client($id, $params);
			} else {
				return null;
			}
		}

        return $this->connections[$id];
    }

}