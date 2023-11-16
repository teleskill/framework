<?php

namespace Teleskill\Framework\Storage;

use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use Teleskill\Framework\Storage\Enums\StoragePermissions;
use Teleskill\Framework\Storage\Disk;

final class SftpDisk extends Disk {

	const LOGGER_NS = self::class;

	/*
	'sftp' => [
		'driver' => StorageDriver::SFTP->value,
		'permissions' => StoragePermissions::WRITE->value,
		'config' => [
			'host' => '', //(required)
			'username' => '', //(required)
			'password' => '', //(optional, default: null) set to null if privateKey is used
			'port' => 22,
			'root' => ''
		]
	]
	*/

	public function __construct(?string $id, array $config, StoragePermissions $permissions, ?string $prefix = null) {
		$adapter = new SftpAdapter(
			new SftpConnectionProvider(
				$config['host'], //(required)
				$config['username'], //(required)
				$config['password'] ?? null, //(optional, default: null) set to null if privateKey is used
				$config['privateKey'] ?? null, // private key (optional, default: null) can be used instead of password, set to null if password is set
				$config['passphrase'] ?? null, // passphrase (optional, default: null), set to null if privateKey is not used or has no passphrase
				$config['port'] ?? 22, // port (optional, default: 22)
				$config['useAgent'] ?? false, // use agent (optional, default: false)
				$config['timeout'] ?? 10, // timeout (optional, default: 10)
				$config['maxTries'] ?? 4, // max tries (optional, default: 4)
				$config['fingerprint'] ?? null, // host fingerprint (optional, default: null),
				null
			),
			$config['root'], // root path (required)
		);

		parent::__construct($id, $adapter, $permissions, $prefix);
	}

	protected function getFullPathName(string $path) : string|null {
		return null;
	}

}