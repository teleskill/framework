<?php

namespace Teleskill\Framework\Storage;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Teleskill\Framework\Storage\Enums\StoragePermissions;
use Teleskill\Framework\Storage\Disk;

final class LocalDisk extends Disk {

	const LOGGER_NS = self::class;
	
	protected string $root;

	public function __construct(?string $id, array $config, StoragePermissions $permissions, ?string $prefix = null) {
		$this->root = $config['root'];

		$adapter = new LocalFilesystemAdapter($config['root']);

		parent::__construct($id, $adapter, $permissions, $prefix);
	}

	public function getFullPathName(string $path) : string|null {
		if ($this->prefix) {
			return $this->root . DIRECTORY_SEPARATOR . $this->prefix . DIRECTORY_SEPARATOR . $path;
		} else {
			return $this->root . DIRECTORY_SEPARATOR . $path;
		}
	}

}