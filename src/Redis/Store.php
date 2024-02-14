<?php

namespace Teleskill\Framework\Redis;

use Teleskill\Framework\Core\App;

abstract class Store {

	const LOGGER_NS = self::class;

	protected ?string $id = null;
	public ?string $prefix = null;
	public bool $tenancy = false;

	public function __construct(string $id) {
		$this->id = $id;
    }
    
}