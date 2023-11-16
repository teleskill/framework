<?php

namespace Teleskill\Framework\Storage\Enums;

enum StoragePermissions : string {
    case READ_ONLY = 'read';
	case WRITE = 'write';
}