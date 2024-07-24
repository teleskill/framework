<?php

namespace Teleskill\Framework\Storage;

use Exception;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Storage\Disk;

final class LocalDisk extends Disk {

	const LOGGER_NS = self::class;

	protected string $root;
	protected mixed $visibility;

	/*
	'local' => [
		'driver' => StorageDriver::LOCAL->value, // required
		'permissions' => StoragePermissions::WRITE->value, // optional
		'visibility' => StorageVisibility::PUBLIC->value, // optional
		'root' => __DIR__ . '/storage', // required
		'prefix' => '/uploads', // optional
		'url' => 'https://www.google.it' // required for public visibility
	]
	*/
	
	public function __construct(?string $id, array $data) {
		parent::__construct($id, $data);

		$this->root = $this->config['root'];
		$this->visibility = $this->config['visibility'] ? PortableVisibilityConverter::fromArray($this->config['visibility']) : null;

		$adapter = new LocalFilesystemAdapter(
			$this->root,
			$this->visibility
		);

		$this->addFileSystem($adapter);
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

	public function moveUploadedFile(string $source_file, string $destinaton_file): bool {
		Log::debug([self::LOGGER_NS, __FUNCTION__], $source_file . ' - ' . $this->root . '/' . $destinaton_file);

		return move_uploaded_file($source_file, $this->root . '/' . $destinaton_file);
	}
}