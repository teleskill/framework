<?php

namespace Teleskill\Framework\MailSender\Enums;

enum MailSend : string {
    case ERROR = 'error';
    case NOT_FOUND = 'not_found';
    case SENT = 'sent';
}