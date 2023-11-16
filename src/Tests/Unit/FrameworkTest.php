<?php

use PHPUnit\Framework\TestCase;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Storage\Storage;
use Teleskill\Framework\OpenID\OpenID;
use Teleskill\Framework\MailSender\MailSender;
use Teleskill\Framework\MailSender\Enums\MailPriority;
use Teleskill\Framework\Cache\Cache;
use Teleskill\Framework\Database\DB;
use Teleskill\Framework\Database\Enums\DBNode;
use Teleskill\Framework\MailSender\Email;
use Teleskill\Framework\Storage\Enums\StoragePermissions;

final class FrameworkTest extends TestCase {

    const LOGGER_NS = self::class;

    public function __construct(string $name) {
        parent::__construct($name);
	}
    
    public function testLogger() : void {
        if (count(Log::list()) > 0) {
            foreach (Log::list() as $id => $item) {
                $this->assertTrue(Log::channel($id)->test([self::LOGGER_NS, __FUNCTION__], 'OK'), 'Success');
            }
        } else {
            $this->assertTrue(true, 'Success');
        }
    }

    public function testStorage() : void {
        if (count(Storage::list()) > 0) {
            foreach (Storage::list() as $id => $item) {
                $storage = Storage::disk($id);

                if ($storage->permissions == StoragePermissions::WRITE) {
                    $file = 'tmp_' . base64_encode(uniqid(rand(), true));

                    $this->assertTrue($storage->write($file, 'TEST'), 'Success');

                    $this->assertTrue($storage->delete($file), 'Success');
                } else {
                    $this->assertNotFalse($storage->listContents('', false), 'Success');
                }
            }
        } else {
            $this->assertTrue(true, 'Success');
        }
    }

    public function testCache() {
        if (count(Cache::list()) > 0) {
            foreach (Cache::list() as $id => $item) {
                $value = base64_encode(uniqid(rand(), true));
                $hash = 'phpunit:test:' . $value;

                $this->assertTrue(Cache::store($id)->set($hash, $value, 30), 'Success');

                $this->assertEquals(Cache::store($id)->get($hash), $value, 'Success');
            }
        } else {
            $this->assertTrue(true, 'Success');
        }
    }

    public function testDb() {
        // test db connection
        if (count(DB::list()) > 0) {
            foreach (DB::list() as $id => $item) {
                $this->assertTrue(DB::conn($id)->open(DBNode::MASTER), 'Success');
                
                $this->assertTrue(DB::conn($id)->open(DBNode::READ_ONLY_REPLICA), 'Success');
            
                DB::conn($id)->close();
            }
        } else {
            $this->assertTrue(true, 'Success');
        }
    }

    public function testOpenID() {
        // test openID
        if (count(OpenID::list()) > 0) {
            foreach (OpenID::list() as $id => $item) {
                $this->assertNotFalse(OpenID::conn($id)->getAccessToken(), 'Success');
            }
        } else {
            $this->assertTrue(true, 'Success');
        }
    }

    public function testMailSender() {
        // enqueue email high priority
        $email = new Email();
        $email->to = 'satzori@teleskill.net';
        $email->subject = 'Messaggio di prova HIGH PRIORITY: ' . time();
        $email->body = 'Questo è un messaggio di prova HIGH PRIORITY';

        if (count(MailSender::list()) > 0) {
            foreach (MailSender::list() as $id => $item) {
                $this->assertTrue(MailSender::mailer($id)->enqueue($email, MailPriority::HIGH), 'Success');
            }
        } else {
            $this->assertTrue(true, 'Success');
        }

        // enqueue email low priority
        $email = new Email();
        $email->to = 'satzori@teleskill.net';
        $email->subject = 'Messaggio di prova LOW PRIORITY: ' . time();
        $email->body = 'Questo è un messaggio di prova LOW PRIORITY';

        if (count(MailSender::list()) > 0) {
            foreach (MailSender::list() as $id => $item) {
                $this->assertTrue(MailSender::mailer($id)->enqueue($email, MailPriority::LOW), 'Success');
            }
        } else {
            $this->assertTrue(true, 'Success');
        }

        /*
        // send email
        $email = new Email();
        $email->to = 'satzori@teleskill.net';
        $email->subject = 'Messaggio di prova diretto: ' . time();
        $email->body = 'Questo è un messaggio di prova diretto';

        if (count(MailSender::list()) > 0) {
            foreach (MailSender::list() as $id => $item) {
                $this->assertTrue(true, 'Success');
               //$this->assertTrue(MailSender::mailer($id)->send($email), 'Success');
            }
        } else {
            $this->assertTrue(true, 'Success');
        }
        */
    }

}