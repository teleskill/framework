<?php

namespace Teleskill\Framework\Storage;

use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use Teleskill\Framework\Storage\Enums\StoragePermissions;
use Teleskill\Framework\Storage\Disk;

final class SftpDisk extends Disk {

	const LOGGER_NS = self::class;

	protected string $host;
	protected string $username;
	protected ?string $password;
	protected ?string $privateKey;
	protected ?string $passphrase;
	protected int $port;
	protected bool $useAgent;
	protected int $timeout;
	protected int $maxTries;
	protected string $root;

	/*
	'sftp' => [
		'driver' => StorageDriver::SFTP->value,
		'prefix' => '{app_id}:',
		'permissions' => StoragePermissions::WRITE->value,
		'host' => '', //(required)
		'username' => '', //(required)
		'password' => '', //(optional, default: null) set to null if privateKey is used
		'port' => 22,
		'root' => ''
	]
	*/

	public function __construct(?string $id, array $data) {
		parent::__construct($id, $data);

		$this->host = $this->config['host']; //(required)
		$this->username = $this->config['username']; //(required)
		$this->password = $this->config['password'] ?? null; //(optional, default: null) set to null if privateKey is used
		$this->privateKey = $this->config['privateKey'] ?? null; // private key (optional, default: null) can be used instead of password, set to null if password is set
		$this->passphrase = $this->config['passphrase'] ?? null; // passphrase (optional, default: null), set to null if privateKey is not used or has no passphrase
		$this->port = $this->config['port'] ?? 22; // port (optional, default: 22)
		$this->useAgent = $this->config['useAgent'] ?? false; // use agent (optional, default: false)
		$this->timeout = $this->config['timeout'] ?? 10; // timeout (optional, default: 10)
		$this->maxTries = $this->config['maxTries'] ?? 4; // max tries (optional, default: 4)
		$this->root = $this->config['root']; // root path (required)

		$adapter = new SftpAdapter(
			connectionProvider: new SftpConnectionProvider(
				host: $this->host, 
				username: $this->username, 
				password: $this->password, 
				privateKey: $this->privateKey, 
				passphrase: $this->passphrase ?? null, 
				port: $this->port ?? 22, 
				useAgent: $this->useAgent ?? false, 
				timeout: $this->timeout ?? 10, 
				maxTries: $this->maxTries ?? 4
			),
			root: $this->root
		);

		$this->addFileSystem($adapter);
	}
	
}