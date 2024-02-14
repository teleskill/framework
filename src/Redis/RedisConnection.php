<?php

namespace Teleskill\Framework\Redis;

use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Redis\Store;
use Teleskill\Framework\Redis\Enums\RedisNode;
use Redis as PhpRedis;
use Exception;

final class RedisConnection  {

	const LOGGER_NS = self::class;

	protected ?string $id = null;
	public ?string $prefix = null;
	protected ?PhpRedis $writeConn;
	protected ?PhpRedis $readconn;
	protected bool $writeConnOpened;
	protected bool $readconnopened;
	public int $db;
	public array $master;
	public ?array $replica;
	
	public function __construct(string $id) {
		$this->id = $id;
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
	
	private function conn(RedisNode $conn_mode) : PhpRedis|null {
		if ($conn_mode == RedisNode::READ_ONLY_REPLICA && $this->replica) {
			$this->openReadConnection();
			
			return $this->readconn;
		} else {
			$this->openWriteConnection();
			
			return $this->writeConn;
		}
	}
    
    public function __call(string $method, array $arguments) : mixed {
		Log::debug([self::LOGGER_NS, __FUNCTION__], ['prefix' => $this->prefix, 'method' => $method, 'arguments' => $arguments]);

		return $this->conn(RedisNode::MASTER)->$method(...$arguments);
    }
	
}