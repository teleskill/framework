<?php

namespace Teleskill\Framework\Cache;

use Teleskill\Framework\Core\App;

abstract class Store {

	const LOGGER_NS = self::class;

	protected ?string $id = null;
	public ?string $prefix = null;
	public bool $tenancy = false;

	public function __construct(string $id) {
		$this->id = $id;
    }

	protected function prefix() : string {
		$prefix = $this->prefix ?? App::id() . ':';

		if ($this->tenancy) {
			//$prefix += App::tenantId() . ':';
		}

		return $prefix;
	}

	abstract public function del(string $key) : void;
	
	abstract public function get(string $key) : array|string|null;
	
	abstract public function set(string $key, mixed $value, ?int $ttl = null);
	
	abstract public function exists(string $key) : bool;
    
}