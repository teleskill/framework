<?php

namespace Teleskill\Framework\Scheduler;

use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Core\App;
use Teleskill\Framework\Cache\Cache;
use Teleskill\Framework\Scheduler\Job;
use Exception;

abstract class CronJob extends Job {

	const LOGGER_NS = self::class;
	
	protected int $ttl;
	
	public function __construct() {
		$options = getopt('', [
			"ttl:"
		]);

		$this->ttl = $options['ttl'];
	}

	protected function isRunnable() : bool {
		$key = 'scheduler:jobs:' . base64_encode(get_class($this));

		// get last run timestamp from the cache
		if (Cache::store($this->cache)->get($key)) {
			return false;
		} else {
			// set timestamp into the cache that expires after ttl
            Cache::store($this->cache)->set($key, App::timestamp(), $this->ttl);

			return true;
		}
	}

	// run job
	public function run() : void  {
		try {
			if ($this->isRunnable()) {
				$this->execute();
			}
		} catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
        }
	}

}
