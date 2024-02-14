<?php

namespace Teleskill\Framework\Redis\Enums;

enum RedisNode : string {
    case MASTER = 'master';
    case READ_ONLY_REPLICA = 'replica';
}

	