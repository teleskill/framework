<?php

namespace Teleskill\Framework\WebApi;

use GuzzleHttp\Client As GuzzleHttpClient;
use Psr\Http\Message\ResponseInterface;
use Teleskill\Framework\WebApi\BodyNone;
use Teleskill\Framework\WebApi\Multipart;
use Teleskill\Framework\WebApi\FormParams;
use Teleskill\Framework\WebApi\RawJson;
use Teleskill\Framework\WebApi\RawText;
use Teleskill\Framework\WebApi\Headers;
use Teleskill\Framework\WebApi\WebApiResponse;
use Teleskill\Framework\WebApi\Enums\WebApiType;
use Teleskill\Framework\WebApi\Enums\WebApiMethod;
use Teleskill\Framework\Logger\Log;
use Exception;

class WebApi {

    const LOGGER_NS = self::class;

    private WebApiMethod $method;
    private WebApiType $type;
    private mixed $data;
    private Headers $headers;
    private string $apiUrl;

    public function __construct(WebApiMethod $method, string $apiUrl, WebApiType $type = WebApiType::NONE, ?array $headers = null) {
        $this->method = $method;
		$this->type = $type;
        $this->headers = new Headers($headers);
        $this->apiUrl = $apiUrl;

        switch ($this->type) {
            case WebApiType::NONE:
                $this->data = new BodyNone();
                break;
            case WebApiType::FORM_PARAMS:
                $this->data = new FormParams();
                break;
            case WebApiType::MULTIPART:
                $this->data = new Multipart();
                break;
            case WebApiType::RAW_JSON:
                $this->data = new RawJson();
                $this->headers->add('Content-Type', 'application/json');
                $this->headers->add('Accept', 'application/json');
                break;
            case WebApiType::RAW_TEXT:
                $this->data = new RawText();
                break;
        }
	}

    public function headers() : Headers {
        return $this->headers;
    }

    public function body() : BodyNone|Multipart|FormParams|RawJson|RawText {
        return $this->data;
    }

    public function send() : WebApiResponse|false {
        switch ($this->method) {
            case WebApiMethod::GET:
                return $this->get();
                break;
            case WebApiMethod::POST:
                return $this->post();
                break;
            case WebApiMethod::PUT:
                return $this->put();
                break;
            case WebApiMethod::PATCH:
                return $this->patch();
                break;
            case WebApiMethod::DELETE:
                return $this->delete();
                break;
        }

        return false;
    }

    private function get() : WebApiResponse|false {
        try {
            $client = new GuzzleHttpClient(['http_errors' => false]);

            $response = $client->get($this->apiUrl, $this->headers->get());

            $webApiResponse = new WebApiResponse($response);

            if ($webApiResponse->success) {
                Log::info([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            } else {
                Log::error([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            }

            return $webApiResponse;

        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], [
                'url' => $this->apiUrl,
                'payload' => $this->data->toString(),
                'exception' => (string) $e
            ]);
        }

        return false;
    }

    private function post() : WebApiResponse|false {
        try {
            $client = new GuzzleHttpClient(['http_errors' => false]);

            $response = $client->post($this->apiUrl, array_merge($this->headers->get(), $this->data->get()));

            $webApiResponse = new WebApiResponse($response);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                Log::info([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'payload' => $this->data->toString(),
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            } else {
                Log::error([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'payload' => $this->data->toString(),
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            }
            
            return $webApiResponse;

        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], [
                'url' => $this->apiUrl,
                'payload' => $this->data->toString(),
                'exception' => (string) $e
            ]);
        }

        return false;
    }

    private function put() : WebApiResponse|false {
        try {
            $client = new GuzzleHttpClient(['http_errors' => false]);

            $response = $client->put($this->apiUrl, array_merge($this->headers->get(), $this->data->get()));

            $webApiResponse = new WebApiResponse($response);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                Log::info([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'payload' => $this->data->toString(),
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            } else {
                Log::error([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'payload' => $this->data->toString(),
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            }
            
            return $webApiResponse;

        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], [
                'url' => $this->apiUrl,
                'payload' => $this->data->toString(),
                'exception' => (string) $e
            ]);
        }

        return false;
    }

    private function patch() : WebApiResponse|false {
        try {
            $client = new GuzzleHttpClient(['http_errors' => false]);

            $response = $client->patch($this->apiUrl, array_merge($this->headers->get(), $this->data->get()));

            $webApiResponse = new WebApiResponse($response);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                Log::info([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'payload' => $this->data->toString(),
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            } else {
                Log::error([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'payload' => $this->data->toString(),
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            }
            
            return $webApiResponse;

        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], [
                'url' => $this->apiUrl,
                'payload' => $this->data->toString(),
                'exception' => (string) $e
            ]);
        }

        return false;
    }

    private function delete() : WebApiResponse|false {
        try {
            $client = new GuzzleHttpClient(['http_errors' => false]);

            $response = $client->delete($this->apiUrl, array_merge($this->headers->get(), $this->data->get()));

            $webApiResponse = new WebApiResponse($response);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                Log::info([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'payload' => $this->data->toString(),
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            } else {
                Log::error([self::LOGGER_NS, __FUNCTION__], [
                    'url' => $this->apiUrl,
                    'payload' => $this->data->toString(),
                    'status' => [
                        'message' => $webApiResponse->statusMessage,
                        'code' => $webApiResponse->statusCode
                    ],
                    'response' => $webApiResponse->content
                ]);
            }
            
            return $webApiResponse;

        } catch (Exception $e) {
            Log::error([self::LOGGER_NS, __FUNCTION__], [
                'url' => $this->apiUrl,
                'payload' => $this->data->toString(),
                'exception' => (string) $e
            ]);
        }

        return false;
    }
    
}