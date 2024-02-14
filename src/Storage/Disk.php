<?php

namespace Teleskill\Framework\Storage;

use League\Flysystem\DirectoryListing;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\FileAttributes;
use Teleskill\Framework\Storage\Enums\StoragePermissions;
use Teleskill\Framework\Logger\Log;
use Exception;

abstract class Disk {

	const LOGGER_NS = self::class;

	protected Filesystem $filesystem;
	public StoragePermissions $permissions;
	protected ?string $id;
	protected ?string $prefix;

	public function __construct(?string $id, array $settings, FilesystemAdapter $adapter) {
		$this->id = $id;
		$this->permissions = StoragePermissions::from($settings['permissions'] ?? StoragePermissions::WRITE);
		if ($this->prefix = $settings['prefix'] ?? null) {
			$this->prefix = str_replace(['app_id', 'tenant_id'], ['lms', '2'], $this->prefix);
		}

		// Turn it into a path-prefixed adapter
		if ($this->prefix) {
			$adapter = new PathPrefixedAdapter($adapter, $this->prefix);
		}

		// Turn it into a read-only adapter
		if ($this->permissions == StoragePermissions::READ_ONLY) {
			$adapter = new ReadOnlyFilesystemAdapter($adapter);
		}

		$this->filesystem = new Filesystem($adapter);
	}

	public function filesystem() : Filesystem|null {
		return $this->filesystem;
	}

	public function write(string $path, $contents, array $config = []) : bool {
		try {
			$this->filesystem->write($path, $contents, $config);

			return true;
		} catch (FilesystemException | UnableToWriteFile $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	public function writeStream(string $path, $stream, array $config = []) : bool {
		try {
			$this->filesystem->writeStream($path, $stream, $config);

			return true;
		} catch (FilesystemException | UnableToWriteFile $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}
       
	public function read(string $path) : string|null {
		try {
			return $this->filesystem->read($path);
		} catch (FilesystemException | UnableToReadFile $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return null;
	}

	public function readStream(string $path) : mixed {
		try {
			return $this->filesystem->readStream($path);
		} catch (FilesystemException | UnableToReadFile $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return null;
	}

	public function delete(string $path) : bool {
		try {
			$this->filesystem->delete($path);

			return true;
		} catch (FilesystemException | UnableToDeleteFile $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	public function deleteDirectory(string $path) : bool {
		try {
			$this->filesystem->deleteDirectory($path);

			return true;
		} catch (FilesystemException | UnableToDeleteDirectory $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	public function listContents(?string $path = '', bool $recursive = false) : DirectoryListing|FALSE {
		try {
			if ($this->filesystem->directoryExists($path)) {
				return $this->filesystem->listContents($path, $recursive);
			}
		} catch (FilesystemException | UnableToCheckExistence $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	public function fileExists(string $path) : bool {
		try {
			return $this->filesystem->fileExists($path);
		} catch (FilesystemException | UnableToCheckExistence $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	public function directoryExists(string $path) : bool {
		try {
			return $this->filesystem->directoryExists($path);
        } catch (FilesystemException | UnableToCheckExistence $exception) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
        }

		return false;
	}

	public function has(string $path) : bool {
		try {
			return $this->filesystem->has($path);
		} catch (FilesystemException | UnableToCheckExistence $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	public function lastModified(string $path) : int|null {
		try {
			return $this->filesystem->lastModified($path);
		} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return null;
	}

	public function mimeType(string $path) : string|null {
		try {
			return $this->filesystem->mimeType($path);
		} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
			
		}

		return null;
	}

	public function fileSize(string $path) : int|null {
		try {
			return $this->filesystem->fileSize($path);
		} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return null;
	}

	public function visibility(string $path): string|null {
		try {
            return $this->filesystem->visibility($path);
        } catch (FilesystemException | UnableToRetrieveMetadata $exception) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
        }

		return null;
	}

	public function setVisibility(string $path, string $visibility = null) : bool {
		try {
            $this->filesystem->setVisibility($path, 'private');

			return true;
        } catch (FilesystemException | UnableToSetVisibility $exception) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
        }

		return false;
	}

	public function createDirectory(string $path, array $config = []) : bool {
		try {
            $this->filesystem->createDirectory($path);

			return true;
        } catch (FilesystemException | UnableToCreateDirectory $exception) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
        }

		return false;
	}

	public function move(string $source, string $destination, array $config = []) : bool {
		try {
			$this->filesystem->move($source, $destination, $config);

			return true;
		} catch (FilesystemException | UnableToMoveFile $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	public function copy(string $source, string $destination, array $config = []) : bool {
		try {
			$this->filesystem->copy($source, $destination, $config);

			return true;
		} catch (FilesystemException | UnableToCopyFile $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	public function copyDirectory(string $sourcepath, string $destinationpath) : bool {
		try {
			$listing = $this->listContents($sourcepath, true);

			foreach ($listing as $item) {
				$path = $item->path();
		
				if ($item instanceof \League\Flysystem\FileAttributes) {
					echo $path . '<br />';
				} elseif ($item instanceof \League\Flysystem\DirectoryAttributes) {
					echo $path . '<br />';
					// handle the directory
				}
			}

			return true;
		} catch (FilesystemException $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}
	
	public function isFile(mixed $file) : bool {
		try {
			if ($file instanceof FileAttributes) {
				return true;
			}
		} catch (Exception $exception) {
			Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);
		}

		return false;
	}

	abstract protected function getFullPathName(string $path) : string|null;

}