<?php

namespace Teleskill\Framework\Cache;

use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Cache\Store;
use Teleskill\Framework\Redis\Redis;
use Teleskill\Framework\Redis\RedisConnection;
use Exception;

final class RedisStore extends Store {

	const LOGGER_NS = self::class;

	protected RedisConnection $connection;

	public function __construct(string $id, string $connectionId) {
		parent::__construct($id, $connectionId);

		$this->connection = Redis::connection($connectionId);
	}

	private function hashPrefix(string $key) {
		return self::HASH_PREFIX . $key;
	}
	
	public function get(string $key, mixed $default = null) : mixed {
		try {
			$hash = $this->hashPrefix($key);

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			$data = unserialize($this->connection->get($hash));

			return $data;

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}
	
	public function add(string $key, mixed $value, ?int $ttl = null) : bool {
		try {
			$hash = $this->hashPrefix($key);

			$data = serialize($value);

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash, 'value' => $data, 'ttl' => $ttl]);

			$options = ['NX'];

			if ($ttl) {
				$options['EX'] = $ttl;
			}
		
			return $this->connection->set($hash, $data, $options);

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}

	public function put(string $key, mixed $value, ?int $ttl = null) : bool {
		try {
			$hash = $this->hashPrefix($key);

			$data = serialize($value);

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash, 'value' => $data, 'ttl' => $ttl]);

			$options = [];

			if ($ttl) {
				$options['EX'] = $ttl;
			}
		
			return $this->connection->set($hash, $data, $options);

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}

	public function has(string $key) : bool {
		try {
			$hash = $this->hashPrefix($key);
			
			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			return $this->connection->exists($hash);
		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}

	public function increment(string $key, ?int $amount = 1) : int|null {
		try {
			$hash = $this->hashPrefix($key);
			
			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			return $this->connection->incr($hash, $amount);
		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return null;
	}

	public function decrement(string $key, ?int $amount = 1) : int|null {
		try {
			$hash = $this->hashPrefix($key);
			
			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			return $this->connection->decr($hash, $amount);

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return null;
	}

	public function pull(string $key) : mixed {
		try {
			$hash = $this->hashPrefix($key);
			
			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			$value = unserialize($this->connection->get($hash));

			$this->connection->del($hash);

			return $value;

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return null;
	}

    public function remember(string $key, int $ttl, mixed $value = null) : bool {
		try {
			$hash = $this->hashPrefix($key);

			$data = serialize($value);

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash, 'value' => $data, 'ttl' => $ttl]);

			$options = [];

			if ($ttl) {
				$options['EX'] = $ttl;
			}
		
			return $this->connection->set($hash, $data, $options);

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}

	public function rememberForever(string $key, mixed $value = null) : bool {
		try {
			$hash = $this->hashPrefix($key);

			$data = serialize($value);

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash, 'value' => $data]);

			return $this->connection->set($hash, $data);

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}

	public function forever(string $key, mixed $value) : bool {
		try {
			$hash = $this->hashPrefix($key);

			$data = serialize($value);

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash, 'value' => $data]);

			return $this->connection->set($hash, $data);

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}

	public function forget(string $key) : void {
		try {
			$hash = $this->hashPrefix($key);

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			$this->connection->del($hash);

		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}
	}
}