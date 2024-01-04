<?php

namespace Teleskill\Framework\OpenID;

use Teleskill\Framework\Core\App;
use Teleskill\Framework\WebApi\WebApi;
use Teleskill\Framework\WebApi\Enums\WebApiType;
use Teleskill\Framework\WebApi\Enums\WebApiMethod;
use Teleskill\Framework\Logger\Log;
use Teleskill\Framework\Cache\Cache;
use Teleskill\Framework\OpenID\Enums\OpenIDGrantType;
use Teleskill\Framework\DateTime\CarbonDateTime;
use Exception;

class Client {

    const LOGGER_NS = self::class;

    const CACHE_PREFIX = 'openid:';
    
    protected string $id;
    private string $tokenUrl;
    private OpenIDGrantType $grantType;
    private ?string $username;
    private ?string $password;
    private ?string $clientId;
    private ?string $clientSecret;
    private ?int $tokenRequestTimeout;
    private string $apiHost;
    private string $timezone;
    private string $inputTimeFormat;
	private string $inputDateFormat;
	private string $inputDateTimeFormat;
	private string $outputTimeFormat;
	private string $outputDateFormat;
	private string $outputDateTimeFormat;
    private string $cache;

    public function __construct(string $id, array $params) {
        $this->id = $id;
        $this->tokenUrl = $params['token_url'];
        $this->grantType = OpenIDGrantType::from($params['grant_type']) ?? OpenIDGrantType::CLIENT_CREDENTIALS;
        $this->username =  $params['username'] ?? NULL;
        $this->password = $params['password'] ?? NULL;
        $this->tokenRequestTimeout = $params['token_request_timeout'] ?? 10;
        $this->clientId = $params['client_id'] ?? NULL;
        $this->clientSecret = $params['client_secret'] ?? NULL;
        $this->apiHost = $params['api_host'];
        $this->timezone = $params['timezone'] ?? 'UTC';
        $this->inputTimeFormat = $params['input']['time_format'];
        $this->inputDateFormat = $params['input']['date_format'];
		$this->inputDateTimeFormat = $params['input']['date_time_format'];
		$this->outputTimeFormat = $params['output']['time_format'];
        $this->outputDateFormat = $params['output']['date_format'];
		$this->outputDateTimeFormat = $params['output']['date_time_format'];
        $this->cache = $params['cache'];
    }

    public function getHeaders() : array {
        $accessToken = $this->getAccessToken();

        return [
            'Authorization' => 'Bearer ' . $accessToken
        ];
    }

    public function getAccessToken() : string|FALSE {
        try {
            $accessToken = Cache::store($this->cache)->get(self::CACHE_PREFIX . $this->id . ':access_token');

            if ($accessToken) {
                Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], 'cached access token: ' . $accessToken);

                return $accessToken;
            } else {
                Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], 'cached access token not found');
            }

            // try to lock new access token request
            if (!Cache::store($this->cache)->set(self::CACHE_PREFIX . $this->id . ':access_token_pending', 1, $this->tokenRequestTimeout, true)) {
                $x = 1;
                do {
                    // wait a second before each retry
                    sleep(1);

                    $accessToken = Cache::store($this->cache)->get(self::CACHE_PREFIX . $this->id . ':access_token');

                    if ($accessToken) {
                        Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], 'cached access token: ' . $accessToken);

                        return $accessToken;

                        break;
                    }

                    $x++;
                } while ($x <= $this->tokenRequestTimeout);

                // timeout expired, access token not found
                return false;
            }

            Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], '[TOKEN] ' . $this->tokenUrl);

            $webApi = new WebApi(WebApiMethod::POST, $this->tokenUrl, WebApiType::FORM_PARAMS);
            
            switch ($this->grantType) {
                case OpenIDGrantType::PASSWORD:
                    $webApi->headers()->set([
                        'Cache-Control' => 'no-cache'
                    ]);

                    $webApi->body()->addField('grant_type', 'password');
                    $webApi->body()->addField('username', $this->username);
                    $webApi->body()->addField('password', $this->password);

                    break;
                case OpenIDGrantType::CLIENT_CREDENTIALS:
                    $webApi->headers()->set([
                        'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
                    ]);

                    $webApi->body()->addField('grant_type', 'client_credentials');

                    break;
            }

            $webApiResponse = $webApi->send();

            if ($webApiResponse && $webApiResponse->success) {
                $jsonContent = json_decode($webApiResponse->content, false);
                $accessToken = $jsonContent->access_token;
                $ttl = $jsonContent->expires_in - 10; // causes the token to expire 10 seconds early

                Log::debug([self::LOGGER_NS, $this->id, __FUNCTION__], 'access token: ' . $accessToken);

                // put access token into the cache
                Cache::store($this->cache)->set(self::CACHE_PREFIX . $this->id . ':access_token', $accessToken, $ttl);

                // delete pending access token request
                Cache::store($this->cache)->del(self::CACHE_PREFIX . $this->id . ':access_token_pending');

                return $accessToken;
            }

        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, $this->id, __FUNCTION__], 'access token -> exception: ' . (string) $e);
        }

        return false;
    }

    public function getBoolean(mixed $value) : bool {
		return ($value == true || $value == 'true');
	}

    public function getDate(?string $date) : CarbonDateTime|null {
		return App::stringToDate($date, $this->inputDateFormat);
	}

	public function getDateTime(?string $date) : CarbonDateTime|null {
		return App::stringToDateTime($date, $this->timezone, $this->inputDateTimeFormat);
	}

	public function setDate(?CarbonDateTime $date) : string|null {
		return App::dateToString($date, $this->outputDateFormat);
	}

	public function setDateTime(?CarbonDateTime $date) : string|null {
		return App::dateTimeToString($date, $this->timezone, $this->outputDateTimeFormat);
	}

    public function webApi(WebApiMethod $method = WebApiMethod::GET, string $url, WebApiType $type = WebApiType::NONE) : WebApi {
		return new WebApi($method, $this->apiHost . $url, $type, $this->getHeaders());
	}
    
}