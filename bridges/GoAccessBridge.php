<?php
class GoAccessBridge extends BridgeAbstract {
	const NAME = 'GoAccess Bridge';
	const URI = 'https://goaccess.io/release-notes';
	const DESCRIPTION = 'Returns release notes for GoAccess.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array());

	const CACHE_TIMEOUT = 3600;

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI);

		$container = $html->find('div.container.content', 0);
		foreach($container->find('div') as $div) {
			$item = array();

			$date = $div->find('small', 0)->plaintext;
			$div->find('small', 0)->innertext = '';

			$item['title'] = $div->find('h2', 0)->plaintext;
			$item['uri'] = self::URI . $div->find('a', 0)->href;
			$item['timestamp'] = $date;

			$content = '';
			foreach ($div->find('dt, dd') as $node) {
				if ($node->tag === 'dt') {
					$text = $node->find('span', 0)->plaintext;

					$content .= <<<HTML
						<li>[{$text}]
					HTML;
				}

				if ($node->tag === 'dd') {
					$text = $node->find('p', 0)->innertext;

					$content .= <<<HTML
						{$text}</li>
					HTML;
				}
			}

			$item['content'] = <<<HTML
				<ul>{$content}</ul>
			HTML;

			$this->items[] = $item;
		}
	}
}
