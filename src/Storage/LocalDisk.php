<?php

namespace Teleskill\Framework\Storage;

use Exception;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Storage\Disk;

final class LocalDisk extends Disk {

	const LOGGER_NS = self::class;

	protected string $root;

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
	
	public function __construct(?string $id, $storageData) {
		$config = $storageData['config'] ?? $storageData['settings'];

		$this->root = $config['root'];
		$this->url = $config['url'] ?? null;

		$adapter = new LocalFilesystemAdapter($this->root);

		parent::__construct($id, $storageData, $adapter);
	}

	public function getFullPathName(string $path) : string|null {
		if ($this->prefix) {
			return $this->sanitizePath($this->root . DIRECTORY_SEPARATOR . $this->prefix . DIRECTORY_SEPARATOR . $path);
		} else {
			return $this->sanitizePath($this->root . DIRECTORY_SEPARATOR . $path);
		}
	}

	public function download(string $path, ?string $save_as = null): void {
		Log::debug([self::LOGGER_NS, __FUNCTION__], [
			'path' => $path,
			'save_as' => $save_as,
		]);

		$file_ext = pathinfo($path, PATHINFO_EXTENSION);
		$file_name = pathinfo($path, PATHINFO_BASENAME);
		$file_size = $this->fileSize($path);
		
		$file_type = Storage::MIME_TYPES[$file_ext] ?? Storage::MIME_TYPES['default'];

		define('CHUNK_SIZE', 1024*1024); // Size (in bytes) of tiles chunk
		
		header('Pragma: no-Cache');
		header('Content-type: ' . $file_type[0]);
		header('Content-length: ' . $file_size);
		header('Content-disposition: attachment; filename=' . ($save_as ?? $file_name));
		header('Content-Transfer-Encoding: binary');

		$this::readFileChunked($this->getFullPathName($path));

		die();
	}

	// Read a file and display its content chunk by chunk
	private static function readFileChunked(string $filename) {
		try {
			Log::debug([self::LOGGER_NS, __FUNCTION__], $filename);

			$buffer = '';
			$handle = fopen($filename, 'rb');

			if ($handle === false) {
				Log::error([self::LOGGER_NS, __FUNCTION__], 'invalid handle');

				return;
			}

			while (!feof($handle)) {
				$buffer = fread($handle, CHUNK_SIZE);
				echo $buffer;
				ob_flush();
				flush();
			}
			
			fclose($handle);

		} catch (Exception $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);	
		}
	} 
}