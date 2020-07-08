<?php
class SignalBridge extends FeedExpander {

	const NAME = 'Signal Bridge';
	const URI = 'https://signal.org/blog/';
	const DESCRIPTION = 'Returns the newest posts from Signal\'s blog';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 3600; // 1 hour

	protected function parseItem($item) {
		$item = parent::parseItem($item);

		$html = getSimpleHTMLDOMCached($item['uri'])
			or returnServerError('Could not request: ' . $item['uri']);

		$html = defaultLinkTo($html, $this->getURI());

		$content = $html->find('div.blog-post-content > div.column.is-7', 0);
		$content->find('div.social-sharing', 0)->outertext = '';
		$item['content'] = $content;

		return $item;
	}

	public function collectData() {
		$this->collectExpandableDatas($this->getURI() . 'rss.xml', 10);
	}
}
