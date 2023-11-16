<?php

namespace Teleskill\Framework\Cache;

use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Cache\Store;
use Teleskill\Framework\Cache\Enums\CacheNode;
use Redis as PhpRedis;
use Exception;

final class RedisStore extends Store {

	const LOGGER_NS = self::class;

	protected ?PhpRedis $writeConn;
	protected ?PhpRedis $readconn;
	protected bool $writeConnOpened;
	protected bool $readconnopened;
	public int $db;
	public array $master;
	public ?array $replica;
	
	public function __construct(string $id) {
		parent::__construct($id);

		$this->writeConn = null;
		$this->readconn = null;
		$this->writeConnOpened = false;
		$this->readconnopened = false;
	}
	
	public function __destruct() {
		$this->writeConn = null;
		$this->readconn = null;
	}
    
    // connect to write host
	private function openWriteConnection() : void {
		if (!$this->writeConnOpened) {
			try {
				$this->writeConn = new PhpRedis();
				$this->writeConn->connect($this->master['host'], $this->master['port']);
				$this->writeConn->select($this->db);
				$this->writeConnOpened = true;
			} catch(Exception $e) {
				Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);

				throw new Exception('Cache exception');
			}
		}
	}
	
	// connect to read host
	private function openReadConnection() : void {
		if (!$this->readconnopened) {
			try {
				$this->readconn = new PhpRedis();
				$this->readconn->connect($this->replica['host'], $this->replica['port']);
				$this->readconn->select($this->db);
				$this->readconnopened = true;
			} catch(Exception $e) {
				Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);

				throw new Exception('Cache exception');
			}
		}
	}
	
	private function conn(CacheNode $conn_mode) : PhpRedis|null {
		if ($conn_mode == CacheNode::READ_ONLY_REPLICA && $this->replica) {
			$this->openReadConnection();
			
			return $this->readconn;
		} else {
			$this->openWriteConnection();
			
			return $this->writeConn;
		}
	}
    
    public function del(string $key) : void {
		try {
			$hash = $this->prefix() . $key;

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			$this->conn(CacheNode::MASTER)->del($hash);
		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}
	}
	
	public function get(string $key) : array|string|null {
		try {
			$hash = $this->prefix() . $key;

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			$data = $this->conn(CacheNode::READ_ONLY_REPLICA)->get($hash);

			if ($data) {
				return json_decode($data, true);
			} else {
				return $data;
			}
		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}
	
	public function set(string $key, mixed $value, ?int $ttl = null, ?bool $notExists = false) : bool {
		try {
			$hash = $this->prefix() . $key;

			if ($value) {
				$data = json_encode($value);
			} else {
				$data = $value;
			}

			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash, 'value' => $data, 'ttl' => $ttl, 'notExists' => $notExists]);

			$options = [];

			if ($notExists) {
				$options[] = 'NX';
			}
			
			if ($ttl) {
				$options['EX'] = $ttl;
			}
		
			return $this->conn(CacheNode::MASTER)->set($hash, $data, $options);
		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}
	
	public function exists(string $key) : bool {
		try {
			$hash = $this->prefix() . $key;
			
			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash]);

			return $this->conn(CacheNode::READ_ONLY_REPLICA)->exists($hash);
		} catch (Exception $e) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
		}

		return false;
	}

	public function rPush(string $key, string $value) : int|false {
		try {
			$hash = $this->prefix() . $key;
			
			Log::debug([self::LOGGER_NS, __FUNCTION__], ['hash' => $hash, 'value' => $value]);

			return $this->conn(CacheNode::MASTER)->rPush($hash, $value);
		} catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
        }

        return false;
	}

	public function lPop(string $key, bool $jsonDecode = false) : string|array|false {
		try {
			$hash = $this->prefix() . $key;
			
			Log::debug([self::LOGGER_NS, __FUNCTION__], $hash);

			$data = $this->conn(CacheNode::MASTER)->lPop($hash);

			if ($data) {
				if ($jsonDecode) {
					return json_decode($data, true);
				} else {
					return $data;
				}
			} else {
				return false;
			}
		} catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
        }

        return false;
	}

	public function rPop(string $key, bool $jsonDecode = false) : string|array|false {
		try {
			$hash = $this->prefix() . $key;
			
			Log::debug([self::LOGGER_NS, __FUNCTION__], $hash);

			$data = $this->conn(CacheNode::MASTER)->rPop($hash);

			if ($data) {
				if ($jsonDecode) {
					return json_decode($data, true);
				} else {
					return $data;
				}
			} else {
				return false;
			}
		} catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
        }

        return false;
	}

	public function lPopAll(string $key) : array {
		$hash = $this->prefix() . $key;
		
		Log::debug([self::LOGGER_NS, __FUNCTION__], $hash);

		return $this->conn(CacheNode::MASTER)->lPop($hash, -1);
	}

	public function rPopAll(string $key) : array {
		$hash = $this->prefix() . $key;
		
		Log::debug([self::LOGGER_NS, __FUNCTION__], $hash);

		return $this->conn(CacheNode::MASTER)->rPop($hash, -1);
	}
	
}