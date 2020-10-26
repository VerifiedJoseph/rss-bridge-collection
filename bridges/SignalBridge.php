<?php
class SignalBridge extends FeedExpander {

	const NAME = 'Signal Messenger Bridge';
	const URI = 'https://signal.org/blog/';
	const DESCRIPTION = 'Returns newest posts from the Signal Messenger blog';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 3600; // 1 hour

	protected function parseItem($item) {
		$item = parent::parseItem($item);

		$html = getSimpleHTMLDOMCached($item['uri'])
			or returnServerError('Could not request: ' . $item['uri']);

		$html = defaultLinkTo($html, $this->getURI());

		$content = $html->find('div.blog-post-content', 0);
		$content->find('div.social-sharing', 0)->outertext = '';
		$item['content'] = $content;

		if ($html->find('meta[property="og:image"]', 0)) {
			$item['enclosures'][] = $html->find('meta[property="og:image"]', 0)->content;
		}

		return $item;
	}

	public function collectData() {
		$this->collectExpandableDatas($this->getURI() . 'rss.xml', 10);
	}
}
