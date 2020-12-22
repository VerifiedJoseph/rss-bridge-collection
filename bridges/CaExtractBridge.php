<?php
class CaExtractBridge extends BridgeAbstract {
	const NAME = 'CA Certificate Extract Bridge';
	const URI = 'https://curl.haxx.se/docs/caextract.html';
	const DESCRIPTION = 'Returns list of Mozilla CA certificate revisions published by curl.haxx.se';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array());

	const CACHE_TIMEOUT = 3600;

	public function collectData() {

		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request: ' . self::URI);

		$html = defaultLinkTo($html, self::URI);

		$content = $html->find('div.contents', 0);

		foreach($content->find('table tr') as $tr) {
			$item = array();

			if (is_null($tr->first_child())) {
				continue;
			}

			if ($tr->first_child()->tag === 'th') {
				continue;
			}

			$date = $tr->children(0)->plaintext;
			$certs = $tr->children(1)->plaintext;
			$link = $tr->children(0)->find('a', 0)->href;

			$item['title'] = $date . ' - ' . $certs .'  Certs';
			$item['uri'] = self::URI;
			$item['timestamp'] = $date;
			$item['content'] = '<a href="' . $link . '">' . $link . '</a>';

			$this->items[] = $item;
		}

	}
}
