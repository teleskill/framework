<?php

namespace Teleskill\Framework\Redis;

use Teleskill\Framework\Cache\RedisStore;
use Teleskill\Framework\Cache\Enums\CacheDriver;
use Teleskill\Framework\Redis\Enums\RedisNode;
use Teleskill\Framework\Config\Config;

class Redis {

	const LOGGER_NS = self::class;

	protected string $default;
	protected array $list = [];
	protected array $stores = [];
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
			self::$instance->list = $config['stores'];
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

		$store = $instance->getStore();

		if ($store) {
			return $store->$method(...$arguments);
		}
        
		return null;
    }

	public static function list() : array {
		$instance = self::getInstance();

		return $instance->list;
    }

	public static function store(string $id) : mixed {
		$instance = self::getInstance();

		return $instance->getStore($id);
    }

	protected function getStore(?string $id = null) : mixed {
		if (!$id) {
			$id = $this->default;
		}

		if (!isset($this->stores[$id])) {
			if (isset($this->list[$id])) {
				$storeData = $this->list[$id];

				$store = new RedisStore($id);
				if ($store->prefix = $storeData['prefix'] ?? null) {
					$store->prefix = str_replace(['app_id', 'tenant_id'], ['lms', '2'], $store->prefix);
				}
				$store->db = $storeData['db'] ?? 0;
				$store->master = $storeData['nodes'][RedisNode::MASTER->value];
				$store->replica = $storeData['nodes'][RedisNode::READ_ONLY_REPLICA->value] ?? null;

				$this->stores[$id] = $store;
				
			} else {
				return null;
			}
		}

		return $this->stores[$id];
	}
    
}