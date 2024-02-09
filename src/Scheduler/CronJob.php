<?php

namespace Teleskill\Framework\Scheduler;

use Teleskill\Framework\Config\Config;

abstract class CronJob {

	const LOGGER_NS = self::class;

	protected string $cache;
	
	public function __construct() {
		$config = Config::get('framework', 'scheduler');

		$this->cache = $config['cache'];
	}

	// execute job
	abstract protected function execute() : bool;
	
	// run job
	public function run() : void  {
		$this->execute();
	}

	protected function print(mixed $value) : void  {
		print_r($value);

		echo PHP_EOL;
	}

}
