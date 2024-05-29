<?php

namespace Teleskill\Framework\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use Teleskill\Framework\Storage\Enums\StorageVisibility;
use Teleskill\Framework\Storage\Enums\StoragePermissions;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Storage\Disk;

final class S3Disk extends Disk {

	const LOGGER_NS = self::class;

	/*
	's3' => [
		'driver' => StorageDriver::S3->value,
		'prefix' => '{app_id}:',
		'permissions' => StoragePermissions::WRITE->value,
		'config' => [
			'accessKeyId' => '',
			'accessKeySecret' => '',
			'region' => '',
			'version' => '',
			'bucket' => ''
		]
	]
	*/

	public function __construct(?string $id, $storageData) {
		$config = $storageData['config'] ?? $storageData['settings'];

		Log::debug([self::LOGGER_NS, __FUNCTION__], [
			'credentials' => [
				'key'    => $config['access_key_id'],
				'secret' => $config['access_key_secret'],
			],
			'region' => $config['region'],
    		'version' => $config['version']
		]);

		$client = new S3Client([
			'credentials' => [
				'key'    => $config['access_key_id'],
				'secret' => $config['access_key_secret'],
			],
			'region' => $config['region'],
    		'version' => $config['version']
		]);

		// The internal adapter
		$adapter = new AwsS3V3Adapter(
			// S3Client
			$client,
			// Bucket name
			$config['bucket'],
			// Optional path prefix
			$prefix ?? '',
			// Visibility converter (League\Flysystem\AwsS3V3\VisibilityConverter)
			new PortableVisibilityConverter(
				$config['visibility'] ?? StorageVisibility::PUBLIC
			),
			null,
			$config['options'] ?? null
		);

		parent::__construct($id, $storageData, $adapter);
	}

	protected function getFullPathName(string $path) : string|null {
		return null;
	}

	public function moveUploadedFile($source_file, $destinaton_file) {
		$stream = fopen($source_file, 'r+');

		$this->writeStream($destinaton_file, $stream);
		
		if (is_resource($stream)) {
			fclose($stream);
		}
	}

}