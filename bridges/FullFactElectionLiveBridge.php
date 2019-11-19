<?php
class FullFactElectionLiveBridge extends BridgeAbstract {
	const NAME = 'Full Fact Election Live Bridge';
	const URI = 'https://fullfact.org';
	const DESCRIPTION = '';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 1800; // 30 mins

	public function collectData() {

		$html = getSimpleHTMLDOM(self::URI . '/electionlive/')
			or returnServerError('Could not request: ' . self::URI);

		$html = defaultLinkTo($html, $this->getURI());

		foreach($html->find('div.live-post-paper') as $index => $post) {
			$item = array();

			$item['title'] = $post->find('h2', 0)->plaintext;
			$item['uri'] = $post->find('a.live-post-time', 0)->href;
			$item['timestamp'] = strtotime($post->find('a.live-post-time', 0)->title);

			foreach($post->find('img') as $index => $img) {
				$post->find('img', $index)->outertext = '<br>' . $img->outertext . '<br>';
			}

			$post->find('a.live-post-time', 0)->outertext = '';
			$post->find('h2', 0)->outertext = '';
			$post->find('div.live-post-social-counters', 0)->outertext = '';

			$item['content'] = trim($post->find('div.copy-wrap', 0)->innertext);

			$this->items[] = $item;
		}
	}
}
