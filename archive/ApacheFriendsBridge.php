<?php
class ApacheFriendsBridge extends FeedExpander {

	const NAME = 'Apache Friends Bridge';
	const URI = 'https://www.apachefriends.org';
	const DESCRIPTION = 'Returns newest XAMPP releases';
	const MAINTAINER = 'VerifiedJoseph';

	const CACHE_TIMEOUT = 3600; // 1 hour

	protected function parseItem($item) {

		// Use published date instead of updated date for the timestamp. 
		// The updated date value is changed every time a new item is added to the feed, making it useless.
		$published = $item->published;

		$item = parent::parseItem($item);

		// Fix broken URLs
		$item['uri'] = str_replace('http://blog.url.com', self::URI, $item['uri']);
		$item['content'] = defaultLinkTo($item['content'], $this->getURI());
		$item['content'] = str_replace('http://', 'https://', $item['content']);

		// Change author from 'Article Author' to 'Apache Friends'.
		$item['author'] = 'Apache Friends';

		// Set timestamp as published date.
		$item['timestamp'] = $published;

		return $item;
	}

	public function collectData() {
		$this->collectExpandableDatas($this->getURI() . '/feed.xml', 20);
	}

	public function getName() {
		return 'Apache Friends';
	}

	public function getURI() {
		return self::URI;
	}
}
