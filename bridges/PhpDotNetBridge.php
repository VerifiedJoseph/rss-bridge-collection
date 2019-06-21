<?php
class PhpDotNetBridge extends BridgeAbstract {
	const NAME = 'php.net News Bridge';
	const URI = 'https://www.php.net';
	const DESCRIPTION = 'Returns news posts from php.net';
	const MAINTAINER = 'VerifiedJoseph';

	const CACHE_TIMEOUT = 3600; // 1 hour

	public function collectData() {
		$item = array();

		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request: ' . self::URI);

		foreach($html->find('article.newsentry') as $entry) {
			$header = $entry->find('header.title', 0);

			$item['title'] = $header->find('h2', 0)->plaintext;
			$item['timestamp'] = strtotime($header->find('time', 0)->datetime);
			$item['uri'] = $header->find('h2 > a', 0)->href;
			$item['content'] = $entry->find('div.newscontent', 0)->innertext;

			$this->items[] = $item;
		}
	}
}
