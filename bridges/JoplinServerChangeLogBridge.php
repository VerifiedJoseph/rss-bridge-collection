<?php
class JoplinServerChangeLogBridge extends BridgeAbstract {
	const NAME = 'Joplin Server Changelog Bridge';
	const URI = 'https://joplinapp.org/changelog_server/';
	const DESCRIPTION = 'Returns release notes for Joplin Server.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array());

	const CACHE_TIMEOUT = 3600;

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request: ' . self::URI);

		$div = $html->find('div.main-content', 0);
		foreach($div->find('h2') as $index => $h2) {
			$item = array();

			$title = str_replace('server-', '', $h2->find('a', 0)->plaintext);
			$h2->find('a', 0)->innertext = '';
			$timestamp = trim($h2->plaintext, ' -🔗');
			$content = $div->find('ul', $index);
			$uri = $h2->find('a', 0)->href;

			$item['title'] = $title;
			$item['timestamp'] = $timestamp;
			$item['content'] = $content;
			$item['uri'] = $uri;

			$this->items[] = $item;
		}
	}
}
