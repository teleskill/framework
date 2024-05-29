<?php

namespace Teleskill\Framework\Storage;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Teleskill\Framework\Storage\Disk;

final class LocalDisk extends Disk {

	const LOGGER_NS = self::class;

	/*
	'local' => [
		'driver' => StorageDriver::LOCAL->value,
		'prefix' => '{app_id}:',
		'permissions' => StoragePermissions::WRITE->value,
		'config' => [
			'root' => __DIR__ . '/storage', // required
		]
	]
	*/
	
	protected string $root;

	public function __construct(?string $id, $storageData) {
		$config = $storageData['config'] ?? $storageData['settings'];

		$this->root = $config['root'];

		$adapter = new LocalFilesystemAdapter($config['root']);

		parent::__construct($id, $storageData, $adapter);
	}

	public function getFullPathName(string $path) : string|null {
		if ($this->prefix) {
			return $this->root . DIRECTORY_SEPARATOR . $this->prefix . DIRECTORY_SEPARATOR . $path;
		} else {
			return $this->root . DIRECTORY_SEPARATOR . $path;
		}
	}

	public function moveUploadedFile(string $source_file, string $destinaton_file): bool {
		return move_uploaded_file($source_file, $destinaton_file);
	}

	public function download(string $path) {

	}

}