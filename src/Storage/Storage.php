<?php

namespace Teleskill\Framework\Storage;

use League\Flysystem\FilesystemException;
use League\Flysystem\MountManager;
use Teleskill\Framework\Config\Config;
use Teleskill\Framework\Storage\Enums\StorageDriver;
use Teleskill\Framework\Storage\Enums\StoragePermissions;
use Teleskill\Framework\Storage\LocalDisk;
use Teleskill\Framework\Storage\SftpDisk;
use Teleskill\Framework\Storage\FtpDisk;
use Teleskill\Framework\Storage\S3Disk;
use Teleskill\Framework\Logger\Log;

class Storage {

	const LOGGER_NS = self::class;

	protected ?string $default = null;
	protected array $list = [];
	protected array $disks = [];
	private static Storage $instance;

	/**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : Storage {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();

			$config = Config::get('framework', 'storage') ?? null;

			if ($config) {
				self::$instance->default = $config['default'];
				self::$instance->list = $config['disks'];
			}
		}

		return self::$instance;
	}

	/**
	* Avoid clone instance
	*/
	public function __clone() {
	}

	/**
	* Avoid serialize instance
	*/
	public function __sleep() {
	}

	/**
	* Avoid unserialize instance
	*/
	public function __wakeup() {
	}

	public static function __callStatic(string $method, array $arguments) {
		$instance = self::getInstance();

		$disk = $instance->getDisk();

		if ($disk) {
			return $disk->$method(...$arguments);
		}
        
		return null;
    }

	public static function list() : array {
		$instance = self::getInstance();

		return $instance->list;
    }

	public static function disk(string $id) : mixed {
		$instance = self::getInstance();

		return $instance->getDisk($id);
    }

	protected function getDisk(?string $id = null) : mixed {
		Log::debug([self::LOGGER_NS, __FUNCTION__], ['id' => $id]);

		if (!$id) {
			$id = $this->default;
		}

		if (!isset($this->disks[$id])) {
			if (isset($this->list[$id])) {
				$params = $this->list[$id];

				$driver = StorageDriver::from($params['driver']);

				switch ($driver) {
					case StorageDriver::LOCAL:
						$disk = new LocalDisk($id, $params['config'], StoragePermissions::from($params['permissions']));
						break;
					case StorageDriver::SFTP:
						$disk = new SftpDisk($id, $params['config'], StoragePermissions::from($params['permissions']));
						break;
					case StorageDriver::FTP:
						$disk = new FtpDisk($id, $params['config'], StoragePermissions::from($params['permissions']));
						break;
					case StorageDriver::S3:
						$disk = new S3Disk($id, $params['config'], StoragePermissions::from($params['permissions']));
						break;
					default:
						return null;
				}

				$this->disks[$id] = $disk;
			} else {
				return null;
			}
		}

		return $this->disks[$id];
	}

	public static function copy(string $source_id, string $source_path, string $destination_id, string $destination_path) : bool {
		$instance = self::getInstance();

		if ($source_id == $destination_id) {
			return $instance->getDisk($source_id)->copy($source_path, $destination_path);
		}

		if (($source = $instance->getDisk($source_id)) && ($destination = $instance->getDisk($destination_id))) {
			try {
				// Add them in the constructor
				$manager = new MountManager([
					'source' => $source->filesystem(),
					'destination' => $destination->filesystem()
				]);

				$manager->copy('source://' . $source_path, 'destination://' . $destination_path);

				return true;
			} catch (FilesystemException $exception) {
				Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
				return false;
			}
		} else {
			return false;
		}
	}

	public static function move(string $source_id, string $source_path, string $destination_id, string $destination_path) : bool {
		Log::debug([self::LOGGER_NS, __FUNCTION__], [
			'source_id' => $source_id,
			'source_path' => $source_path,
			'destination_id' => $destination_id,
			'destination_path' => $destination_path
		]);

		$instance = self::getInstance();

		if ($source_id == $destination_id) {
			return $instance->getDisk($source_id)->move($source_path, $destination_path);
		}

		$source = $instance->getDisk($source_id);
		$destination = $instance->getDisk($destination_id);

		if ($source && $destination) {
			try {
				// Add them in the constructor
				$manager = new MountManager([
					'source' => $source->filesystem(),
					'destination' => $destination->filesystem()
				]);

				$manager->move('source://' . $source_path, 'destination://' .  $destination_path);

				return true;
			} catch (FilesystemException $exception) {
				Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
				return false;
			}
		} else {
			return false;
		}
	}

	public static function copyDirectory(string $source_id, string $source_path, string $destination_id, string $destination_path) : bool {
		Log::debug([self::LOGGER_NS, __FUNCTION__], [
			'source_id' => $source_id,
			'source_path' => $source_path,
			'destination_id' => $destination_id,
			'destination_path' => $destination_path
		]);

		$instance = self::getInstance();

		if ($source_id == $destination_id) {
			return $instance->getDisk($source_id)->copyDirectory($source_path, $destination_path);
		}

		$source = $instance->getDisk($source_id);
		$destination = $instance->getDisk($destination_id);

		if ($source && $destination) {
			try {
				// Add them in the constructor
				$manager = new MountManager([
					'source' => $source->filesystem(),
					'destination' => $destination->filesystem()
				]);

				$contents = $source->listContents($source_path, true);

				if ($contents) {
					foreach ($contents as $object) {
						//get differences in path
						$path = self::relativePath($object['path'], $source_path);
						
						if ($object['type'] == 'file') {
							$manager->copy('source://' . $path, 'destination://' .  $path);
						} elseif ($object['type'] == 'dir') {
							$destination->createDirectory($path);
						}
					}
				}

				return true;
			} catch (FilesystemException $exception) {
				Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
				return false;
			}
		}
	}

	public static function formatFileSize(int $bytes, int $precision = 2) : string {
		$units = array('Bytes', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		$res = pow(1024, $pow);
		if ($res > 0) {
			$bytes /= pow(1024, $pow);
		} else {
			$bytes = 0;
		}
		//$bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/**
	 * Function: sanitize
	 * Returns a sanitized string, typically for URLs . 
	 *
	 * Parameters:
	 *     $string - The string to sanitize . 
	 *     $force_lowercase - Force the string to lowercase?
	 *     $strict - If set to *true*, will remove all non-alphanumeric characters . 
	 */
	public static function sanitizeFileName(string $string, bool $force_lowercase = true, bool $strict = false) : string {
		$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
			"}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
			"—", "–", ",", "<", " . ", ">", "/", "?");
		$clean = trim(str_replace($strip, "", strip_tags($string)));
		$clean = preg_replace('/\s+/', "-", $clean);
		$clean = ($strict) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean;
		return ($force_lowercase) ?
			(function_exists('mb_strtolower')) ?
			mb_strtolower($clean, 'UTF-8') :
			strtolower($clean) :
			$clean;
	}

	public static function relativePath(string $from, string $to) : string {
		//get differences in path
		$diff = array_diff(explode(DIRECTORY_SEPARATOR, $from), explode(DIRECTORY_SEPARATOR, $to));

		return implode(DIRECTORY_SEPARATOR, $diff);
	}
}