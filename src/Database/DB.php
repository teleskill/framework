<?php

namespace Teleskill\Framework\Database;

use Teleskill\Framework\Database\Connection;
use Teleskill\Framework\Database\Eloquent;
use Teleskill\Framework\Config\Config;
use Teleskill\Framework\Core\App;
use Teleskill\Framework\Database\Enums\DBHandler;


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
				$this->addConnection($id, DBHandler::tryFrom($connection['handler'] ?? DBHandler::STANDARD->value), $this->list[$id]['settings']);
			} else {
				return null;
			}
		}

        return $this->connections[$id];
    }

	public function addConnection(string $id, DBHandler $handler, array $settings) : void {
		switch($handler) {
			case DBHandler::STANDARD:
				$this->connections[$id] = new Connection($id, $settings);
				break;
			case DBHandler::ELOQUENT:
				$settings['database'] = str_replace(['app_id'], [App::id()], $settings['database']);
				$this->connections[$id] = new Eloquent($id, $settings);
				break;
		}		
    }

	public static function boot() : void {
		$instance = self::getInstance();

		foreach($instance->list as $id => $connection) {
			$instance->addConnection($id, (DBHandler::tryFrom($connection['handler']) ?? DBHandler::STANDARD), $connection['settings']);
		}
	}
}
