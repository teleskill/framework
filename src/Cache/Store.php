<?php

namespace Teleskill\Framework\Cache;

use Teleskill\Framework\Core\App;

abstract class Store {

	const LOGGER_NS = self::class;

	const HASH_PREFIX = 'cache:';

	protected ?string $id = null;
	protected ?string $connectionId = null;

	public function __construct(string $id, string $connectionId) {
		$this->id = $id;
		$this->connectionId = $connectionId;
    }

	abstract public function get(string $key, mixed $default = null) : mixed;

	abstract public function add(string $key, mixed $value, ?int $ttl = null) : bool;

	abstract public function put(string $key, mixed $value, ?int $ttl = null) : bool;
	
	abstract public function has(string $key) : bool;

	abstract public function increment(string $key, ?int $amount) : int|null;

	abstract public function decrement(string $key, ?int $amount) : int|null;

	abstract public function pull(string $key) : mixed;

    abstract public function remember(string $key, int $ttl, mixed $default = null) : bool;

	abstract public function rememberForever(string $key, mixed $default = null) : bool;

	abstract public function forever(string $key, mixed $value) : bool;

	abstract public function forget(string $key) : void;
}