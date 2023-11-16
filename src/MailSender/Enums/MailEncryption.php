<?php

namespace Teleskill\Framework\MailSender\Enums;

enum MailEncryption : string {
    case SSL = 'ssl';
    case TLS = 'tls';
    case NONE = 'none';
}