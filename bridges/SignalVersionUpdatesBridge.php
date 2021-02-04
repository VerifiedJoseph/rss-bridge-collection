<?php

class SignalVersionUpdatesBridge extends BridgeAbstract {
	const NAME = 'Signal version updates';
	const URI = 'https://signal.org/';
	const CACHE_TIMEOUT = 3600;
	const DESCRIPTION = 'Returns version updates for Signal';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	private $jsonUrl = 'https://updates.signal.org/android/latest.json';

	public function collectData() {
		$response = $this->get($this->jsonUrl);;
		$data = json_decode($response['body']);

		$item['title'] = '5.3.7.1';
		$item['timestamp'] = $response['headers']['Last-Modified'];
		$item['content'] = <<<EOD
<strong>Version code</strong>
<p>{$data->versionCode}</p>
<strong>Date</strong>
<p>{$response['headers']['Last-Modified']}</p>
<strong>sha256 hash</strong>
<p>{$data->sha256sum}</p>
<strong>Download</strong>
<p><a href="{$data->url}">{$data->url}</a></p>
EOD;

		$this->items[] = $item;
	}

	private function get($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		$data = curl_exec($curl);
		$response = curl_getinfo($curl);
		$errorCode = curl_errno($curl);

		if ($errorCode !== 0 || $response['http_code'] !== 200) {
			returnClientError('Failed to fetch: ' . $url);
		}

		$body = substr($data, $response['header_size']);
		$headers = $this->getHeaders($data, $response['header_size']);

		return array(
			'headers' => $headers,
			'body' => $body
		);
	}
	
	private function getHeaders($data, $size) {
		$headers = array();

		$headerText = substr($data, 0, $size);
		$body = substr($data, $size);

		if(preg_match_all('/([a-zA-Z-0-9]+): ?(.*)\b/', trim($headerText), $matches, PREG_SET_ORDER, 0)) {
			foreach ($matches as $header) {
				$headers[$header[1]] = trim($header[2]);
			}
		}

		return $headers;
	}
}
