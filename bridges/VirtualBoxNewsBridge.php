<?php
class VirtualBoxNewsBridge extends BridgeAbstract {
	const NAME = 'VirtualBox News Bridge';
	const URI = 'https://www.virtualbox.org';
	const DESCRIPTION = 'Returns project news';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 3600;

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI() . '/wiki/News')
			or returnServerError('Could not request: ' . $this->getURI() . '/wiki/News');

		$html = defaultLinkTo($html, $this->getURI());
		
		$wikipage = $html->find('div[id=wikipage]', 0);

		foreach($wikipage->find('p') as $index => $p) {
			$item = array();

			$date = $p->find('strong', 0)->plaintext;

			$item['title'] = $date;
			$item['timestamp'] = $date;

			$p->find('strong', 0)->innertext = '';
			
			$item['content'] = $p->innertext;

			$this->items[] = $item;

			if (count($this->items) >= 10) {
				break;
			}
		}
	}
}
