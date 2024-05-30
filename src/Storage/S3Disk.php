<?php

namespace Teleskill\Framework\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Teleskill\Framework\Config\Enums\Environment;
use Teleskill\Framework\Core\App;
use Teleskill\Framework\DateTime\CarbonDateTime;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Storage\Disk;

final class S3Disk extends Disk {

	const LOGGER_NS = self::class;

	protected S3Client $s3Client;
	protected ?string $key;
	protected ?string $secret;
	protected ?string $endpoint;
	protected string $region;
	protected ?string $version;
	protected string $bucket;
	protected array $options = [];

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

		$this->key = $config['key'] ?? null;
		$this->secret = $config['secret'] ?? null;
		$this->endpoint = $config['endpoint'] ?? null;
		$this->region = $config['region'];
		$this->version = $config['version'] ?? null;
		$this->bucket = $config['bucket'];
		$this->prefix = $config['prefix'] ?? '';
		$this->options = $config['options'] ?? [];
		$this->visibility = $config['visibility'] ?? null;
		$this->url = $config['url'] ?? null;

		$s3Params = [];

		if ($this->key || $this->secret) {
			$s3Params['credentials'] = [
				'key'    => $this->key ?? null,
				'secret' => $this->secret ?? null,
			];
		}

		if ($this->endpoint) {
			$s3Params['endpoint'] = $this->endpoint;
		}

		if ($this->region) {
			$s3Params['region'] = $this->region;
		}

		if ($this->version) {
			$s3Params['version'] = $this->version;
		}

		Log::debug([self::LOGGER_NS, __FUNCTION__], $s3Params);

		$this->s3Client = new S3Client($s3Params);

		// The internal adapter
		$adapter = new AwsS3V3Adapter(
			client: $this->s3Client, // S3Client
			bucket: $this->bucket, // Bucket name
			prefix: $this->prefix, // Optional path prefix
			options: $this->options // Options
			/*
			// Visibility converter (League\Flysystem\AwsS3V3\VisibilityConverter)
			new PortableVisibilityConverter(
				$this->visibility
			),
			*/
		);

		parent::__construct($id, $storageData, $adapter);
	}

	public function download(string $path, ?string $save_as = null): void {
		Log::debug([self::LOGGER_NS, __FUNCTION__], [
			'path' => $path,
			'save_as' => $save_as,
		]);

		$url = $this->temporaryUrl($path, App::now()->addMinutes(5), [
			'ResponseContentDisposition' => "attachment;filename={$save_as}"
		]);

		header("Location: {$url}");
		
		exit();
	}

	public function temporaryUrl(string $path, CarbonDateTime $expiresAt, array $options = []): string|null {
		$cmd = $this->s3Client->getCommand('GetObject', array_merge([
			'Bucket' => $this->bucket,
			'Key' => $path,
		], $options));
		
		$url = (string) ($this->s3Client->createPresignedRequest($cmd, '+500 minutes'))->getUri();

		if ($this->url && App::getEnv() == Environment::DEV) {
			$url = str_replace($this->endpoint, $this->url, $url);
		}
		
		Log::debug([self::LOGGER_NS, __FUNCTION__], $url);

		/*
		// Non funziona
		$url = $this->filesystem->temporaryUrl($path, $expiresAt, $options);

		Log::debug([self::LOGGER_NS, __FUNCTION__], $url);
		*/

		return $url;
	}
	
}