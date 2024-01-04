<?php

namespace Teleskill\Framework\Database;

use Teleskill\Framework\Core\App;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Database\Enums\DBNode;
use Teleskill\Framework\DateTime\CarbonDateTime;
use PDO;
use Exception;

class Connection {
	
	const LOGGER_NS = self::class;

	private string $id;
	private string $username;
	private string $password;
	private array $attributes;
	private string $inputTimeFormat;
	private string $inputDateFormat;
	private string $inputDateTimeFormat;
	private string $outputTimeFormat;
	private string $outputDateFormat;
	private string $outputDateTimeFormat;
	private string $timezone;
	private string $writeDsn;
	private ?string $readDsn = null;
	private ?PDO $writeConn = null;
	private ?PDO $readConn = null;
	private bool $writeConnOpened = false;
	private bool $readConnOpened = false;
	private bool $transaction = false;

	public function __construct(string $id, array $params) {
		$this->id = $id;
		$this->timezone = $params['timezone'] ?? 'UTC';
		$this->inputTimeFormat = $params['input']['time_format'];
        $this->inputDateFormat = $params['input']['date_format'];
		$this->inputDateTimeFormat = $params['input']['date_time_format'];
		$this->outputTimeFormat = $params['output']['time_format'];
        $this->outputDateFormat = $params['output']['date_format'];
		$this->outputDateTimeFormat = $params['output']['date_time_format'];
		$this->username = $params['username'];
		$this->password = $params['password'];
		$this->attributes = $params['attributes'];
		$this->writeDsn = $params['nodes'][DBNode::MASTER->value]['dsn'];
		$this->readDsn = $params['nodes'][DBNode::READ_ONLY_REPLICA->value]['dsn'] ?? NULL;

		$this->writeConnOpened = false;
		$this->readConnOpened = false;
	}
	
	public function __destruct() {
		$this->writeConn = null;
		$this->readConn = null;
		$this->writeConnOpened = false;
		$this->readConnOpened = false;
	}

	public function close() : void {
		$this->writeConn = null;
		$this->readConn = null;
		$this->writeConnOpened = false;
		$this->readConnOpened = false;
    }
	
	// connect using pdo
	private function openWriteConnection() : bool  {
		if (!$this->writeConnOpened) {
			try {
				$this->writeConn = new PDO($this->writeDsn, $this->username, $this->password, $this->attributes);
				
				$this->writeConnOpened = true;
			} catch(Exception $e) {
				Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

				throw new Exception('DB exception');

				return false;
			}
		}

		return true;
	}
	
	// connect using pdo
	private function openReadConnection() : bool {
		if (!$this->readConnOpened) {
			try {
				$this->readConn = new PDO($this->readDsn, $this->username, $this->password, $this->attributes);
				
				$this->readConnOpened = true;
			} catch(Exception $e) {
				Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

				throw new Exception('DB exception');

				return false;
			}
		}

		return true;
	}

	public function open(DBNode $connMode) : bool {
		if ($this->conn($connMode)) {
			return true;
		} else {
			return false;
		}
	}
	
	private function conn(DBNode $connMode) : PDO|null {
		if (!$this->transaction && $connMode == DBNode::READ_ONLY_REPLICA && $this->readDsn) {
			if (!$this->readConnOpened) {
				$this->openReadConnection();
			}
			
			return $this->readConn;
		} else {
			if (!$this->writeConnOpened) {
				$this->openWriteConnection();
			}
			
			return $this->writeConn;
		}
	}

	public function executeNonQuery(string $query, array $params = []) : void {
		try {
			Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], $query . PHP_EOL . json_encode($params));

			$stmt = $this->conn(DBNode::MASTER)->prepare($query);
			$stmt->execute($params);
			$stmt = null;
		} catch(Exception $e) {
			Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

			throw new Exception('DB exception');
		}
	}

	public function insertRow(string $query, array $params = []) : int|null {
		try {
			Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], $query . PHP_EOL . json_encode($params));
			
			$stmt = $this->conn(DBNode::MASTER)->prepare($query);
			$stmt->execute($params);
			$stmt = null;

			return $this->conn(DBNode::MASTER)->lastInsertId();
		} catch(Exception $e) {
			Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

			throw new Exception('DB exception');
		}

		return null;
	}

	public function getRows(string $query, array $params = [], int $fetch_mode = PDO::FETCH_ASSOC) : array|FALSE {
		try {
			Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], $query . PHP_EOL . json_encode($params));

			$stmt = $this->conn(DBNode::READ_ONLY_REPLICA)->prepare($query);
			$stmt->execute($params);
			$rows = $stmt->fetchAll($fetch_mode);
			$stmt = null;

			return $rows;
		} catch(Exception $e) {
			Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

			throw new Exception('DB exception');
		}
		
		return false;		
	}
	
	public function getRow(string $query, array $params = []) : array|null {
		try {
			Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], $query . PHP_EOL . json_encode($params));

			$stmt = $this->conn(DBNode::READ_ONLY_REPLICA)->prepare($query);
			$stmt->execute($params);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt = null;

			if ($row) {
				return $row;
			} else {
				return null;
			}
		} catch(Exception $e) {
			Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

			throw new Exception('DB exception');
		}
		
		return null;
	}

	public function beginTransaction() : bool {
		try {
			Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__]);

			if (!$this->transaction) {
				$this->conn(DBNode::MASTER)->beginTransaction();

				$this->transaction = true;
			}

			return true;
		} catch(Exception $e) {
			Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

			throw new Exception('DB exception');
		}
		
		return false;
	}

	public function rollbackTransaction() : bool {
		try {
			Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__]);

			if ($this->transaction) {
				$this->conn(DBNode::MASTER)->rollBack();

				$this->transaction = false;
			}

			return true;
		} catch(Exception $e) {
			Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);
			
			throw new Exception('DB exception');
		}
		
		return false;
	}

	public function commitTransaction() : bool {
		try {
			Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__]);

			if ($this->transaction) {
				$this->conn(DBNode::MASTER)->commit();

				$this->transaction = false;
			}

			return true;
		} catch(Exception $e) {
			Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

			throw new Exception('DB exception');
		}
		
		return false;
	}

	public function getBoolean(mixed $value) : bool {
		return ($value == 1 || $value == '1');
	}

	public function getDate(?string $date) : CarbonDateTime|null {
		return App::stringToDate($date, $this->inputDateFormat);
	}

	public function getDateTime(?string $date) : CarbonDateTime|null {
		return App::stringToDateTime($date, $this->timezone, $this->inputDateTimeFormat);
	}

	public function setDate(?CarbonDateTime $date) : string|null {
		return App::dateToString($date, $this->outputDateFormat);
	}

	public function setDateTime(?CarbonDateTime $date) : string|null {
		return App::dateTimeToString($date, $this->timezone, $this->outputDateTimeFormat);
	}

	public function getTimeZone() : string|null {
		return $this->timezone;
	}

}
