<?php

namespace Teleskill\Framework\Storage\Enums;

use League\Flysystem\Visibility;

enum StorageVisibility : string {
    case PUBLIC = Visibility::PUBLIC;
	case PRIVATE = Visibility::PRIVATE;
}