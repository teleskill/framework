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
	*/

	public function __construct(?string $id, array $data) {
		parent::__construct($id, $data);

		$adapter = new FtpAdapter(
			// Connection options
			FtpConnectionOptions::fromArray([
				'host' => $this->config['host'], // required
				'root' => $this->config['root'], // required
				'username' => $this->config['username'] ?? null, // required
				'password' => $this->config['password'] ?? null, // required
				'port' => $this->config['port'] ?? null,
				'ssl' => $this->config['ssl'] ?? null,
				'timeout' => $this->config['timeout'] ?? null,
				'utf8' => $this->config['utf8'] ?? null,
				'passive' => $this->config['passive'] ?? false,
				'transferMode' => $this->config['transferMode'] ?? null,
				'systemType' => $this->config['systemType'] ?? 'unix', // 'windows' or 'unix'
				'ignorePassiveAddress' => $this->config['ignorePassiveAddress'], // true or false
				'timestampsOnUnixListingsEnabled' => $this->config['timestampsOnUnixListingsEnabled'], // true or false
				'recurseManually' => $this->config['recurseManually'] // true 
			])
		);

		$this->addFileSystem($adapter);
	}

}