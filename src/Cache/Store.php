<?php

namespace Teleskill\Framework\Cache;

use Teleskill\Framework\Core\App;
use Stringable;

abstract class Store {

	const LOGGER_NS = self::class;

	protected ?string $id = null;
	public ?string $prefix = null;

	public function __construct(string $id) {
		$this->id = $id;
    }

	protected function prefix() : string {
		return ($this->prefix ?? App::id()) . ':';
	}

	abstract public function del(string $key) : void;
	
	abstract public function get(string $key) : array|string|null;
	
	abstract public function set(string $key, mixed $value, ?int $ttl = null);
	
	abstract public function exists(string $key) : bool;
    
}