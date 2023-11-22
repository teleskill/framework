<?php

namespace Teleskill\Framework\MailSender;

use Teleskill\Framework\Config\Config;
use Teleskill\Framework\MailSender\Enums\MailTransport;
use Teleskill\Framework\MailSender\Enums\MailEncryption;

class MailSender {

	protected ?string $default = null;
	protected array $list = [];
	protected array $mailers = [];
	private static MailSender $instance;

	/**
	* Get Instance
	*
	* @return Singleton
	*/
	final public static function getInstance() : MailSender {
		if (!isset(self::$instance)) {
            $class = get_called_class();
            
			self::$instance = new $class();

			$config = Config::get('framework', 'mailSender') ?? null;

			if ($config) {
				self::$instance->default = $config['default'];
				self::$instance->list = $config['mailers'];
			}
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

	public static function __callStatic(string $method, array $arguments) {
		$instance = self::getInstance();

		$mailer = $instance->getMailer();

		if ($mailer) {
			return $mailer->$method(...$arguments);
		}
        
		return null;
    }

	public static function list() : array {
		$instance = self::getInstance();

		return $instance->list;
    }

	public static function mailer(string $id) : mixed {
		$instance = self::getInstance();

		return $instance->getMailer($id);
    }

	protected function getMailer(?string $id = null) : mixed {
		if (!$id) {
			$id = $this->default;
		}

		if (!isset($this->mailers[$id])) {
			if (isset($this->list[$id])) {
				$params = $this->list[$id];

				$transport = MailTransport::from($params['transport']);

				switch ($transport) {
					case MailTransport::SMTP:
						$mailer = new SmtpMailer($id);
						$mailer->host = $params['host'];
						$mailer->port = $params['port'];
						$mailer->encryption = MailEncryption::from($params['encryption']);
						$mailer->username = $params['username'];
						$mailer->password = $params['password'];
						$mailer->from = $params['from'];
						$mailer->fromName = $params['from_name'];

						$this->mailers[$id] = $mailer;

						break;
					default:
						return null;
				}
			} else {
				return null;
			}
		}

		return $this->mailers[$id];
	}

}