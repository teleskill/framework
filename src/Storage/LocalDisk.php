<?php

namespace Teleskill\Framework\Storage;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Teleskill\Framework\Storage\Disk;

final class LocalDisk extends Disk {

	const LOGGER_NS = self::class;

	/*
	'local' => [
		'driver' => StorageDriver::LOCAL->value,
		'settings' => [
			'prefix' => '{app_id}:',
			'permissions' => StoragePermissions::WRITE->value,
			'config' => [
				'root' => __DIR__ . '/storage', // required
			]
		]
	]
	*/
	
	protected string $root;

	public function __construct(?string $id, $settings) {
		$config = $settings['config'];

		$this->root = $config['root'];

		$adapter = new LocalFilesystemAdapter($config['root']);

		parent::__construct($id, $settings, $adapter);
	}

	public function getFullPathName(string $path) : string|null {
		if ($this->prefix) {
			return $this->root . DIRECTORY_SEPARATOR . $this->prefix . DIRECTORY_SEPARATOR . $path;
		} else {
			return $this->root . DIRECTORY_SEPARATOR . $path;
		}
	}

}