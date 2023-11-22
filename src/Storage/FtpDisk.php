<?php

namespace Teleskill\Framework\Storage;

use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use Teleskill\Framework\Storage\Enums\StoragePermissions;
use Teleskill\Framework\Storage\Disk;

final class FtpDisk extends Disk {

	const LOGGER_NS = self::class;

	/*
	'ftp' => [
		'driver' => StorageDriver::FTP->value,
		'permissions' => StoragePermissions::WRITE->value,
		'config' => [
			'host' => 'hostname', // required
			'root' => '/root/path/', // required
			'username' => 'username', // required
			'password' => 'password', // required
			'port' => 21,
			'ssl' => false,
			'timeout' => 90,
			'utf8' => false,
			'passive' => true,
			'transferMode' => FTP_BINARY,
			'systemType' => null, // 'windows' or 'unix'
			'ignorePassiveAddress' => null, // true or false
			'timestampsOnUnixListingsEnabled' => false, // true or false
			'recurseManually' => true // true
		]
	]
	*/

	public function __construct(?string $id, array $config, StoragePermissions $permissions, ?string $prefix = null) {
		$adapter = new FtpAdapter(
			// Connection options
			FtpConnectionOptions::fromArray([
				'host' => $config['host'], // required
				'root' => $config['root'], // required
				'username' => $config['username'], // required
				'password' => $config['password'], // required
				'port' => $config['port'],
				'ssl' => $config['ssl'],
				'timeout' => $config['timeout'],
				'utf8' => $config['utf8'],
				'passive' => $config['passive'],
				'transferMode' => $config['transferMode'],
				'systemType' => $config['systemType'], // 'windows' or 'unix'
				'ignorePassiveAddress' => $config['ignorePassiveAddress'], // true or false
				'timestampsOnUnixListingsEnabled' => $config['timestampsOnUnixListingsEnabled'], // true or false
				'recurseManually' => $config['recurseManually'] // true 
			])
		);

		parent::__construct($id, $adapter, $permissions, $prefix);
	}

	protected function getFullPathName(string $path) : string|null {
		return null;
	}

}