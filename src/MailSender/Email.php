<?php

namespace Teleskill\Framework\MailSender;

class Email {

	const LOGGER_NS = self::class;

	public ?string $from = null;
    public ?string $fromName = null;
    public ?string $to = null;
    public ?string $subject = null;
    public ?string $body = null;
    public ?string $cc = null;

	public function __construct() {

    }
    
}