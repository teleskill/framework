<?php

namespace Teleskill\Framework\WebApi;

use Psr\Http\Message\ResponseInterface;

class WebApiResponse {

    public bool $success = false;
    public ?int $statusCode = null;
    public ?string $statusMessage = null;
    public ?string $content = null;

    public function __construct(ResponseInterface $response) {
        $this->statusCode = $response->getStatusCode();
        $this->statusMessage = $response->getReasonPhrase();
        $this->content = $response->getBody()->getContents();

        if ($this->statusCode >= 200 && $this->statusCode <= 299) {
            $this->success = true;
        } else {
            $this->success = false;
        }
	}
        
}