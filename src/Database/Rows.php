<?php

namespace Teleskill\Framework\Database;

use Iterator;

class Rows implements Iterator {

    const LOGGER_NS = self::class;

    private ?array $args;
    private array $rows = [];
    private int $index = 0;
    private string $callback;

	public function __construct(array $rows, string $callback, $args = null) {
        $this->rows = $rows;
		$this->args = $args;
        $this->callback = $callback;
	}

    public function current() : mixed {
        // get current recordset row
		$row = $this->rows[$this->index];

        if ($this->args) {
            return call_user_func($this->callback, $row, $this->args);
        } else {
            return call_user_func($this->callback, $row);
        }
    }

    public function next(): void {
        $this->index ++;
    }

    public function key(): mixed {
        return $this->index;
    }

    public function valid(): bool {
        return isset($this->rows[$this->key()]);
    }

    public function rewind(): void {
        $this->index = 0;
    }

    public function reverse(): void {
        $this->rows = array_reverse($this->rows);
        $this->rewind();
    }

}