<?php

namespace Teleskill\Framework\Redis;

use Teleskill\Framework\Redis\RedisConnection;
use Teleskill\Framework\Redis\Enums\RedisNode;
use Teleskill\Framework\Config\Config;
use Teleskill\Framework\Core\App;

class Redis {

	const LOGGER_NS = self::class;

	protected string $default;
	protected array $list = [];
	protected array $connections = [];
	private static Redis $instance;

	private function __construct()
    {
        // Private constructor to prevent direct instantiation
    }

	/**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : Redis {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();

			$config = Config::get('framework', 'redis');

			self::$instance->default = $config['default'];
			self::$instance->list = $config['connections'];
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

		$connection = $instance->getConnection();

		if ($connection) {
			return $connection->$method(...$arguments);
		}
        
		return null;
    }

	public static function list() : array {
		$instance = self::getInstance();

		return $instance->list;
    }

	public static function connection(string $id) : RedisConnection {
		$instance = self::getInstance();

		return $instance->getConnection($id);
    }

	protected function getConnection(?string $id = null) : RedisConnection {
		if (!$id) {
			$id = $this->default;
		}

		if (!isset($this->connections[$id])) {
			if (isset($this->list[$id])) {
				$settings = $this->list[$id];

				$connection = new RedisConnection($id);
				$connection->prefix = ($settings['prefix'] ?? App::id()) . ':';
				$connection->db = $settings['db'] ?? 0;
				$connection->master = $settings['nodes'][RedisNode::MASTER->value];
				$connection->replica = $settings['nodes'][RedisNode::READ_ONLY_REPLICA->value] ?? null;

				$this->connections[$id] = $connection;
				
			} else {
				return null;
			}
		}

		return $this->connections[$id];
	}
    
}