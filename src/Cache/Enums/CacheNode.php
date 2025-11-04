<?php

namespace Teleskill\Framework\Cache\Enums;

enum CacheNode : string {
    case MASTER = 'master';
    case READ_ONLY_REPLICA = 'replica';
}

	