<?php

namespace Teleskill\Framework\Storage\Enums;

enum StorageDriver : string {
    case S3 = 's3';
	case LOCAL = 'local';
    case SFTP = 'sftp';
    case FTP = 'ftp';
}