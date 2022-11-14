<?php
class CyberDuckChangeLogBridge extends BridgeAbstract {
	const NAME = 'Cyberduck Changelog Bridge';
	const URI = 'https://cyberduck.io/changelog/';
	const DESCRIPTION = 'Returns release notes for Cyberduck.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array());

	const CACHE_TIMEOUT = 3600;

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request: ' . self::URI);

		$table = $html->find('table', 0);
		foreach($table->find('tr') as $tr) {
			$item = array();

			$version = $tr->children(0)->find('strong', 0)->plaintext;
			$tracLink = $tr->children(0)->find('a', 0)->href;
			$date = $tr->children(0)->find('em', 0)->plaintext;

			$changeLog = $this->formatChangeLog($tr->children(1)->find('ul', 0));
			$downloads = $this->formatDownloads($tr);

			$item['title'] = $version;
			$item['timestamp'] = $date;
			$item['uid'] = $version . $date;

			$item['content'] = <<<EOD
<strong>Changelog</strong><p>{$changeLog}</p><strong>Trac Milestone</strong>
<p><a href="{$tracLink}">{$tracLink}</a></p><strong>Downloads</strong><p>{$downloads}</p>
EOD;

			$this->items[] = $item;

			if (count($this->items) >= 10) {
				break;
			}
		}
	}

	private function formatChangeLog($html) {
		foreach ($html->find('span.label') as $span) {
			$span->innertext = '[' . $span->innertext . ']';
		}

		return $html;
	}

	private function formatDownloads($tr) {
		$links = '';

		foreach ($tr->children(0)->find('a.btn') as $a) {
			$a->innertext .= '(' . $a->href . ')';
			$links .= $a . '<br>';
		}

		return $links;
	}
}
