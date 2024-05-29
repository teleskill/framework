<?php

namespace Teleskill\Framework\Storage;

use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;

class CloudFront {
	private $client;
	
	public function __construct($key, $secret) {
		$credentials = new \Aws\Credentials\Credentials($key, $secret);
		
		$this->client = new CloudFrontClient([
	        'profile' => 'default',
	        'version'     => Config::get('settings', 'STORAGE')['version'],
		    'region'      => Config::get('settings', 'STORAGE')['region'],
	        'credentials' => $credentials
		]);
	}
	
	public function __destruct() {
		
	}
	
	public function createPresignedUrl($resource_key, $key_pair_id, $file_name) {
		try {
			$result = $this->client->getSignedUrl([
			    'url' => $resource_key,
			    'expires' => time() + 120,
			    'private_key' => Config::get('settings', 'ROOT').'/cloudfront/pk-' . $key_pair_id.'.pem',
			    'key_pair_id' => $key_pair_id,
			    'ResponseContentDisposition' => 'attachment; filename="' . $file_name.'"'
			]);

			return $result;
		} catch (AwsException $e) {
			//echo 'Error: ' . $e->getAwsErrorMessage();
			return '';
		}
	}
}