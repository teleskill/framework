<?php

namespace Teleskill\Framework\OpenID\Enums;

enum OpenIDGrantType : string {
    case CLIENT_CREDENTIALS = 'client_credentials';
    case PASSWORD = 'password';
}