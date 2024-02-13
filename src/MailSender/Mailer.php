<?php

namespace Teleskill\Framework\MailSender;

use Teleskill\Framework\MailSender\Enums\MailTransport;

abstract class Mailer {

    const LOGGER_NS = self::class;

    const CACHE_PREFIX = 'mailsender:';

	protected ?string $id;
    public MailTransport $transport;
    public string $from;
    public string $fromName;
    public string $to;
    public string $subject;
    public string $body;
    public bool $enqueue = true;

	public function __construct(?string $id) {
		$this->id = $id;
    }

    public function send(Email $email) : bool {
        if ($this->enqueue) {
            MailQueue::append($this, $email);

            return true;
        }
    }
    
}