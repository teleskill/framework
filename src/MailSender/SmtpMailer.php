<?php

namespace Teleskill\Framework\MailSender;

use Symfony\Component\Mailer\Transport as SymfonyTransport;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Teleskill\Framework\MailSender\Email;
use Teleskill\Framework\MailSender\MailQueue;
use Teleskill\Framework\MailSender\Mailer;
use Teleskill\Framework\MailSender\Enums\MailEncryption;
use Teleskill\Framework\MailSender\Enums\MailTransport;
use Teleskill\Framework\MailSender\Enums\MailPriority;
use Teleskill\Framework\Logger\Log;
use Exception;

final class SmtpMailer extends Mailer {

    const LOGGER_NS = self::class;

    public string $host;
    public string $port;
    public MailEncryption $encryption;
    public string $username;
    public string $password;
    public string $from;
    public string $fromName;

    public function __construct(?string $id = null) {
		parent::__construct($id);

        $this->transport = MailTransport::SMTP;
	}

    public function send(Email $email) : bool {
        try {
            Log::info([self::LOGGER_NS, __FUNCTION__], $email->to . PHP_EOL . $email->subject . PHP_EOL . $email->body . PHP_EOL);

            $conn = 'smtp://' . $this->username . ':' . $this->password . '@'. $this->host . ':' . $this->port;
            if ($this->encryption != MailEncryption::NONE) {
                $conn = $conn . '&/encryption=' . $this->encryption->value;
            }

            $transport = SymfonyTransport::fromDsn($conn);
            
            $mailer = new SymfonyMailer($transport);

            $symfonyEmail = (new SymfonyEmail())
                ->from(new SymfonyAddress($email->from ?? $this->from, $email->fromName ?? $this->fromName))
                ->to($email->to)
                ->subject($email->subject)
                ->html($email->body);

            $mailer->send($symfonyEmail);

            return true;
        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
        }

        return false;
    }

    public function enqueue(Email $email, MailPriority $priority) : bool {
        try {
            MailQueue::add($this, $email, $priority);

            return true;
        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $e);
        }

        return false;
    }

    

}