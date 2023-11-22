<?php

namespace Teleskill\Framework\Database\Enums;

enum DBNode : string {
    case MASTER = 'master';
    case READ_ONLY_REPLICA = 'replica';
}

	