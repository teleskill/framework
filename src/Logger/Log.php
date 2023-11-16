<?php

namespace Teleskill\Framework\Logger;

use Teleskill\Framework\Logger\StreamLogger;
use Teleskill\Framework\Logger\Enums\LogHandler;
use Teleskill\Framework\Config\Config;

class Log {

	const LOGGER_NS = self::class;

	protected ?string $default = null;
    protected array $list = [];
    protected array $channels = [];
    private static Log $instance;

    /**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : Log {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();

			$config = Config::get('framework', 'log') ?? null;

			if ($config) {
				self::$instance->default = $config['default'];
				self::$instance->list = $config['channels'];
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

		$channel = $instance->getChannel();

        if ($channel) {
			return $channel->$method(...$arguments);
		}
        
		return null;
    }

	public static function list() : array {
		$instance = self::getInstance();

		return $instance->list;
    }
    
	public static function channel(string $id) : mixed {
		$instance = self::getInstance();

		return $instance->getChannel($id);
    }

    protected function getChannel(?string $id = null) : mixed {
		if (!$id) {
			$id = $this->default;
		}

		if (!isset($this->channels[$id])) {
			if (isset($this->list[$id])) {
				$params = $this->list[$id];

				$handler = LogHandler::from($params['handler']);

				switch ($handler) {
					case LogHandler::STREAM:
						$this->channels[$id] = new StreamLogger($id, $params);
						break;
					default:
						return null;
				}
			} else {
				return null;
			}
		}

        return $this->channels[$id];
    }

}