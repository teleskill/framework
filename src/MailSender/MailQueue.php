<?php

namespace Teleskill\Framework\MailSender;

use Exception;
use Teleskill\Framework\Core\App;
use Teleskill\Framework\Config\Config;
use Teleskill\Framework\MailSender\SmtpMailer;
use Teleskill\Framework\MailSender\Enums\MailTransport;
use Teleskill\Framework\MailSender\Enums\MailEncryption;
use Teleskill\Framework\MailSender\Enums\MailPriority;
use Teleskill\Framework\MailSender\Enums\MailSend;
use Teleskill\Framework\MailSender\Email;
use Teleskill\Framework\Cache\Cache;
use Teleskill\Framework\Logger\Log;

class MailQueue {

	const LOGGER_NS = self::class;

	protected string $cache;

	private static MailQueue $instance;

	/**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : MailQueue {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();

			$config = Config::get('framework', 'mailSender');

			self::$instance->cache = $config['cache'];
		}

		return self::$instance;
	}

	/**
	* Avoid clone instance
	*/
	public function __clone() {
	}

	/**
	* Avoid serialize instance
	*/
	public function __sleep() {
	}

	/**
	* Avoid unserialize instance
	*/
	public function __wakeup() {
	}

    public static function add(mixed $mailer, Email $email, MailPriority $priority) : bool {
        $instance = self::getInstance();

        $data = [
            'application' => App::id(),
            'timestamp' => App::timestamp(),
        ];

        switch(get_class($mailer)) {
            case 'Teleskill\Framework\MailSender\SmtpMailer':
                $data['mailer'] = [
                    'transport' => $mailer->transport,
                    'host' => $mailer->host,
                    'port' => $mailer->port,
                    'encryption' => $mailer->encryption,
                    'username' => $mailer->username,
                    'password' => $mailer->password
                ];
                break;
            default:
                return false;
        }

        $data['email'] = [
            'from' => $email->from ?? $mailer->from,
            'from_name' => $email->fromName ?? $mailer->fromName,
            'to' => $email->to,
            'cc' => $email->cc,
            'bcc' => $email->bcc,
            'subject' => $email->subject,
            'body' => $email->body
        ];

        $hash = 'queue:' . $priority->value;

        Cache::store($instance->cache)->rPush($hash, json_encode($data));

        return true;
    }

    public static function send(MailPriority $priority) : MailSend {
        $instance = self::getInstance();

        $hash = 'queue:' . $priority->value;

        $data = Cache::store($instance->cache)->lPop($hash);

        try {
            if ($data) {
                $jsonData = json_decode($data, true);

                if ($jsonData) {
                    Log::info([self::LOGGER_NS, __FUNCTION__], 'priority: ' . $priority->value . ' - data: ' . $data);

                    switch(MailTransport::tryFrom($jsonData['mailer']['transport'])) {
                        case MailTransport::SMTP:
                            $mailer = new SmtpMailer();
                            $mailer->host = $jsonData['mailer']['host'];
                            $mailer->port = $jsonData['mailer']['port'];
                            $mailer->encryption = MailEncryption::tryFrom($jsonData['mailer']['encryption']) ?? MailEncryption::NONE;
                            $mailer->username = $jsonData['mailer']['username'];
                            $mailer->password = $jsonData['mailer']['password'];
                            break;
                        default:
                            Log::error([self::LOGGER_NS, __FUNCTION__], 'mailer transport not found');
                            
                            return MailSend::ERROR;
                    }

                    $email = new Email();
                    $email->from = $jsonData['email']['from'];
                    $email->fromName = $jsonData['email']['from_name'];
                    $email->to = $jsonData['email']['to'];
                    $email->cc = $jsonData['email']['cc'] ?? [];
                    $email->bcc = $jsonData['email']['bcc'] ?? [];
                    $email->subject = $jsonData['email']['subject'];
                    $email->body = $jsonData['email']['body'];
                    $mailer->send($email);

                    $email = null;
                    $mailer = null;

                    return MailSend::SENT;
                }

                Log::error([self::LOGGER_NS, __FUNCTION__], 'priority: ' . $priority->value . ' - data: ' . $data);

                return MailSend::ERROR;
            }
        } catch (Exception $exception) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);

            return MailSend::ERROR;
        }

        return MailSend::NOT_FOUND;
    }
    
}