<?php

namespace Teleskill\Framework\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;
use Teleskill\Framework\Logger\Enums\LogLevel;
use Teleskill\Framework\Logger\Enums\LogHandler;
use Teleskill\Framework\MailSender\MailSender;
use Teleskill\Framework\MailSender\Email;
use Teleskill\Framework\MailSender\Enums\MailPriority;
use Stringable;
use Exception;

abstract class MonoLogger {
    
    const LOGGER_NS = self::class;

    protected string $id;
    protected Logger $monoLogger;
    protected LineFormatter $formatter;
    protected LogLevel $level;
    protected LogHandler $handler;
    protected string $file;
    protected ?array $emailNotify;

    public function __construct(string $id, array $config) {
        try {
            $this->id = $id;
            $this->level = LogLevel::from($config['level']);
            $this->handler = LogHandler::from($config['handler']);
            $this->file = $config['file'];
            $this->emailNotify = $config['email_notify'] ?? null;
            
            $guid = Uuid::uuid4() . '-' . date('Ymd');

            // init MonoLogger
            $this->monoLogger = new Logger('log');

            // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
            $output = "[$guid] | %datetime% | %level_name% | %message%\n\n";

            // finally, create a formatter
            $this->formatter = new LineFormatter($output, "Y-m-d H:i:s");

        } catch(Exception $e) {}
    }

    public function format(array $referrer, mixed $message) : null|string {
        try {
            switch (gettype($message)) {
                case 'array':
                    return '[' . implode('\\', $referrer) . '] | ' . json_encode($message);
                    break;
                case 'string':
                    return '[' . implode('\\', $referrer) . '] | ' . $message;
                    break;
                default:
                    return '[' . implode('\\', $referrer) . '] | ' . (string) $message;
                    break;
            }
        } catch(Exception $e) {}

        return null;
    }

    public function error(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::ERROR, $referrer, $message, $context);
        } catch(Exception $e) {}

        return false;
    }

    public function info(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::INFO, $referrer, $message, $context);
        } catch(Exception $e) {}

        return false;
    }

    public function debug(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::DEBUG, $referrer, $message, $context);
        } catch(Exception $e) {}

        return false;
    }

    public function notice(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::NOTICE, $referrer, $message, $context);
        } catch(Exception $e) {}

        return false;
    }

    public function warning(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::WARNING, $referrer, $message, $context);
        } catch(Exception $e) {}

        return false;
    }

    public function critical(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::CRITICAL, $referrer, $message, $context);
        } catch(Exception $e) {}

        return false;
    }

    public function alert(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::ALERT, $referrer, $message, $context);
        } catch(Exception $e) {}

        return false;
    }

    public function emergency(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::EMERGENCY, $referrer, $message, $context);
        } catch(Exception $e) {
            
		}

        return false;
    }

    public function test(array $referrer, string|Stringable|array $message = null, array $context = []) : bool {
        try {
            return $this->log(LogLevel::TEST, $referrer, $message, $context);
        } catch(Exception $e) {
            
		}

        return false;
    }

    private function log(LogLevel $level, array $referrer, string|Stringable|array $message = null, array $context = []) {
        try {
            if ($this->level->value > $level->value) {
                return false;
            }

            $data = $this->format($referrer, $message);
            
            switch ($level) {
                case LogLevel::DEBUG:
                    $this->monoLogger->debug($data, $context);
                    break;
                case LogLevel::INFO:
                    $this->monoLogger->info($data, $context);
                    break;
                case LogLevel::NOTICE:
                    $this->monoLogger->notice($data, $context);
                    break;
                case LogLevel::WARNING:
                    $this->monoLogger->warning($data, $context);
                    break;
                case LogLevel::ERROR:
                    $this->monoLogger->error($data, $context);
                    break;
                case LogLevel::CRITICAL:
                    $this->monoLogger->critical($data, $context);
                    break;
                case LogLevel::ALERT:
                    $this->monoLogger->alert($data, $context);
                    break;
                case LogLevel::EMERGENCY:
                    $this->monoLogger->emergency($data, $context);
                    break;
                case LogLevel::TEST:
                    $this->monoLogger->debug($data, $context);
                    break;
            }
            
            if ($this->emailNotify && $this->emailNotify['level'] <= $level->value) {
                // Invio della mail di notifica
                $email = new Email();
                $email->fromName = $this->emailNotify['from_name'];
                $email->to = $this->emailNotify['to'];
                $email->subject = $this->emailNotify['subject'];
                $email->body = str_replace('{ERRORE}', $data, $this->emailNotify['body']);

                // Invio email
                MailSender::enqueue($email, MailPriority::HIGH);
            }

            return true;

        } catch(Exception $e) {
            
		}

        return false;
    }

}