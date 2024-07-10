<?php

namespace Teleskill\Framework\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use Teleskill\Framework\Config\Enums\Environment;
use Teleskill\Framework\Core\App;
use Teleskill\Framework\DateTime\CarbonDateTime;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Storage\Disk;
use Teleskill\Framework\Storage\Enums\StorageVisibility;

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
	protected string $visibility;

	/*
	's3' => [
		'driver' => StorageDriver::S3->value,
		'prefix' => '{app_id}:',
		'permissions' => StoragePermissions::WRITE->value, // optional
		'visibility' => StorageVisibility::PUBLIC->value, // optional
		'key' => '',
		'secret' => '',
		'region' => '',
		'version' => '',
		'bucket' => '',
		'prefix' => '/uploads', // optional
		'url' => 'https://www.google.it' // required for public visibility
	]
	*/

	public function __construct(?string $id, array $data) {
		parent::__construct($id, $data);

		$this->key = $this->config['key'] ?? null;
		$this->secret = $this->config['secret'] ?? null;
		$this->endpoint = $this->config['endpoint'] ?? null;
		$this->region = $this->config['region'];
		$this->version = $this->config['version'] ?? null;
		$this->bucket = $this->config['bucket'];
		$this->prefix = $this->config['prefix'] ?? '';
		$this->options = $this->config['options'] ?? [];
		$this->visibility = $this->config['visibility'] ?? StorageVisibility::PUBLIC->value;

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

		$this->s3Client = new S3Client($s3Params);

		// The internal adapter
		$adapter = new AwsS3V3Adapter(
			client: $this->s3Client, // S3Client
			bucket: $this->bucket, // Bucket name
			prefix: $this->prefix, // Optional path prefix
			options: $this->options, // Options
			visibility: new PortableVisibilityConverter($this->visibility) // Visibility converter (League\Flysystem\AwsS3V3\VisibilityConverter)
		);

		$this->addFileSystem($adapter);
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

	public function temporaryUrl(string $path, int|string|CarbonDateTime $expiresAt, array $options = []): string|null {
		$cmd = $this->s3Client->getCommand('GetObject', array_merge([
			'Bucket' => $this->bucket,
			'Key' => $path,
		], $options));
		
		$url = (string) ($this->s3Client->createPresignedRequest($cmd, $expiresAt))->getUri();

		if ($this->url) {
			$url = str_replace($this->sanitizePath($this->endpoint . '/' . $this->bucket), $this->url, $url);
		}
		
		$url = $this->sanitizePath($url);

		Log::debug([self::LOGGER_NS, __FUNCTION__], $url);

		return $url;
	}
	
}