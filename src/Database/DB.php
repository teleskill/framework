<?php

namespace Teleskill\Framework\Database;

use Teleskill\Framework\Database\Connection;
use Teleskill\Framework\Database\Eloquent;
use Teleskill\Framework\Config\Config;
use Teleskill\Framework\Database\Enums\DBDriver;

class DB {

	const LOGGER_NS = self::class;
	
	protected ?string $default = null;
	protected array $list = [];
    protected array $connections = [];
    private static DB $instance;

    /**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : DB {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();

			$config = Config::get('framework', 'db') ?? null;

			if ($config) {
				self::$instance->default = $config['default'];
				self::$instance->list = $config['connections'];
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

	public static function conn(string $id) : Connection|null {
		$instance = self::getInstance();

		return $instance->getConn($id);
    }

	protected function getConn(?string $id = null) : Connection|null {
		if (!$id) {
			$id = $this->default;
		}

		if (!isset($this->connections[$id])) {
			if (isset($this->list[$id])) {
				$params = $this->list[$id];

				$this->addConnection($id, $params);
			} else {
				return null;
			}
		}

        return $this->connections[$id];
    }

	public function addConnection(string $id, array $params) : void {
		switch(DBDriver::tryFrom($params['driver'] ?? DBDriver::DEFAULT->value)) {
			case DBDriver::DEFAULT:
				$this->connections[$id] = new Connection($id, $params);
				break;
			case DBDriver::DEFAULT:
				$this->connections[$id] = new Eloquent($id, $params);
				break;
		}
		
    }
}
