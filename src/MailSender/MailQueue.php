<?php

namespace Teleskill\Framework\MailSender;

use Exception;
use Ramsey\Uuid\Uuid;
use Teleskill\Framework\Core\App;
use Teleskill\Framework\Config\Config;
use Teleskill\Framework\MailSender\SmtpMailer;
use Teleskill\Framework\MailSender\Enums\MailTransport;
use Teleskill\Framework\MailSender\Enums\MailEncryption;
use Teleskill\Framework\MailSender\Enums\MailPriority;
use Teleskill\Framework\MailSender\Enums\MailSend;
use Teleskill\Framework\MailSender\Email;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Redis\Redis;
use Teleskill\Framework\WebApi\Enums\WebApiMethod;
use Teleskill\Framework\WebApi\Enums\WebApiType;
use Teleskill\Framework\WebApi\WebApi;

class MailQueue {

	const LOGGER_NS = self::class;

	protected string $redis;
    protected ?string $apiUrl;
    protected ?string $apiKey;

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

			self::$instance->redis = Config::get('framework', 'mailSender')['redis'];
            self::$instance->apiUrl = Config::get('framework', 'mailSender')['api_url'] ?? null;
			self::$instance->apiKey = Config::get('framework', 'mailSender')['api_key'] ?? null;
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

    public static function enqueue(mixed $mailer, Email $email) : bool {
        $instance = self::getInstance();

        $data = [];

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
            [
                'from' => $email->from ?? $mailer->from,
                'from_name' => $email->fromName ?? $mailer->fromName,
                'to' => $email->to,
                'cc' => $email->cc,
                'bcc' => $email->bcc,
                'subject' => $email->subject,
                'body' => $email->body,
                'priority' => $email->priority->value
            ]
        ];

        $webApi = new WebApi(WebApiMethod::PUT, $instance->apiUrl, WebApiType::RAW_JSON);
        $webApi->body()->set($data);
        $webApiResponse = $webApi->send();

        if ($webApiResponse && $webApiResponse->success) {
            return true;
        }
        
        return false;
    }

    public static function append(mixed $mailer, Email $email) : bool {
        $instance = self::getInstance();

        $data = [
            'application' => App::id(),
            'timestamp' => App::timestamp(),
            'uuid' => Uuid::uuid4()
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
            'body' => $email->body,
            'priority' => $email->priority->value
        ];

        $hash = 'mailsender:queue:' . $email->priority->value;

        Redis::connection($instance->redis)->rPush($hash, json_encode($data));
                  
        Log::info([self::LOGGER_NS, __FUNCTION__], $data);

        return true;
    }

    public static function send(MailPriority $priority) : MailSend {
        $instance = self::getInstance();

        $hash = 'mailsender:queue:' . $priority->value;

        $data = Redis::connection($instance->cache)->lPop($hash);

        try {
            if ($data) {
                $jsonData = json_decode($data, true);

                if ($jsonData) {
                    try {
                        if ($uuid = $jsonData['uuid'] ?? NULL) {
                            Log::info([self::LOGGER_NS, __FUNCTION__], ['uuid' => $uuid, 'status' => 'delivery in progress...']);
                        } else {
                            $uuid = Uuid::uuid4();
                            
                            Log::info([self::LOGGER_NS, __FUNCTION__], ['uuid' => $uuid, 'data' => $jsonData]);
                        }

                        $email = new Email();
                        $email->from = $jsonData['email']['from'];
                        $email->fromName = $jsonData['email']['from_name'];
                        $email->to = $jsonData['email']['to'];
                        $email->cc = $jsonData['email']['cc'] ?? [];
                        $email->bcc = $jsonData['email']['bcc'] ?? [];
                        $email->subject = $jsonData['email']['subject'];
                        $email->body = $jsonData['email']['body'];

                        switch(MailTransport::tryFrom($jsonData['mailer']['transport'])) {
                            case MailTransport::SMTP:
                                $mailer = new SmtpMailer();
                                $mailer->enqueue = false;
                                $mailer->host = $jsonData['mailer']['host'];
                                $mailer->port = $jsonData['mailer']['port'];
                                $mailer->encryption = MailEncryption::tryFrom($jsonData['mailer']['encryption']) ?? MailEncryption::NONE;
                                $mailer->username = $jsonData['mailer']['username'];
                                $mailer->password = $jsonData['mailer']['password'];
                                break;
                            default:
                                Log::error([self::LOGGER_NS, __FUNCTION__], ['uuid' => $uuid, 'status' => 'mailer transport is not valid']);
                                
                                return MailSend::ERROR;
                        }
                        
                        if ($mailer->send($email)) {
                            Log::info([self::LOGGER_NS, __FUNCTION__], ['uuid' => $uuid, 'status' => 'email successfully delivered']);

                            return MailSend::SENT;
                        } else {
                            Log::error([self::LOGGER_NS, __FUNCTION__], ['uuid' => $uuid, 'status' => 'delivery failure']);

                            return MailSend::ERROR;
                        }
                    } catch (Exception $exception) {
                        Log::error([self::LOGGER_NS, __FUNCTION__], ['uuid' => $uuid, 'status' => (string) $exception]);

                        return MailSend::ERROR;
                    }
                }

                Log::error([self::LOGGER_NS, __FUNCTION__], ['priority' => $priority->value, 'data' => $data]);

                return MailSend::ERROR;
            }
        } catch (Exception $exception) {
            Log::error([self::LOGGER_NS, __FUNCTION__], (string) $exception);

            return MailSend::ERROR;
        }

        return MailSend::NOT_FOUND;
    }
    
}