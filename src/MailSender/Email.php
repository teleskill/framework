<?php

namespace Teleskill\Framework\MailSender;

class Email {

	const LOGGER_NS = self::class;

	public ?string $from = null;
    public ?string $fromName = null;
    public ?string $to = null;
    public ?string $subject = null;
    public ?string $body = null;
    public array $cc = [];
    public array $bcc = [];
    public array $attachments = [];

	public function __construct() {

    }

    public function attach(string $file) : bool {
        $this->attachments[] = $file;

        return true;
    }
    
}