<?php

namespace Teleskill\Framework\DynamoDB;

use Aws\DynamoDb\DynamoDbClient;
use Teleskill\Framework\Core\App;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\DateTime\CarbonDateTime;
use Exception;

class DynamoDBConnection {
	
	const LOGGER_NS = self::class;

	private string $id;
	private ?DynamoDbClient $client;
	private ?array $credentials;
	private string $inputDateFormat;
	private string $inputDateTimeFormat;
	private string $outputDateFormat;
	private string $outputDateTimeFormat;
	private string $timezone;
	private string $region;
	private string $version;
	private ?string $endpoint;
	private bool $opened = false;

	public function __construct(string $id, array $params) {
		$this->id = $id;
		$this->timezone = $params['timezone'] ?? 'UTC';
		$this->region = $params['region'];
		$this->version = $params['version'];
		$this->endpoint = $params['endpoint'] ?? null;
        $this->inputDateFormat = $params['input']['date_format'];
		$this->inputDateTimeFormat = $params['input']['date_time_format'];
        $this->outputDateFormat = $params['output']['date_format'];
		$this->outputDateTimeFormat = $params['output']['date_time_format'];
		$this->credentials = $params['credentials'] ?? null;

		$this->opened = false;
	}
	
	public function __destruct() {
		$this->close();
	}

	public function close() : void {
		$this->client = null;
		$this->opened = false;
    }
	
	public function open() : bool {
		if ($this->client()) {
			return true;
		} else {
			return false;
		}
	}
	
	private function client() : DynamoDbClient|null {
		if (!$this->opened) {
			try {
				$args = [
					'region' => $this->region,
					'version' => $this->version,
				];
				if ($this->endpoint) {
					$args['endpoint'] = $this->endpoint;
				}
				if ($this->credentials) {
					$args['credentials'] = $this->credentials;
				}

				$this->client = new DynamoDbClient($args);
				
				$this->opened = true;
			} catch(Exception $e) {
				Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], (string) $e);

				throw new Exception('DB exception');

				return null;
			}
		}

		return $this->client;
	}

	public function createTable(array $args) : void {
		$this->client()->createTable($args);
	}

	public function query(array $query) : mixed {
		return $this->client()->query($query);
	}

	public function deleteItem(array $query) : mixed {
		return $this->client()->deleteItem($query);
	}

	public function batchWriteItem(array $query) : mixed {
		return $this->client()->batchWriteItem($query);
	}

	public function getBoolean(mixed $value) : bool {
		return ($value == 1 || $value == '1');
	}

	public function setBoolean(mixed $value) : bool {
		return $value;
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
