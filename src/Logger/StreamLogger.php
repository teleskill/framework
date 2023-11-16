<?php

namespace Teleskill\Framework\Logger;

use Monolog\Level;
use Monolog\Handler\RotatingFileHandler;
use Teleskill\Framework\Logger\MonoLogger;

final class StreamLogger extends MonoLogger {

	const LOGGER_NS = self::class;

    const CACHE_PREFIX = 'log:';

	public function __construct(string $id, array $config) {
		parent::__construct($id, $config);

		$streamHandler = new RotatingFileHandler($this->file, $config['days'], Level::Debug);        
        
        $streamHandler->setFormatter($this->formatter);

		$this->monoLogger->pushHandler($streamHandler);
	}

}