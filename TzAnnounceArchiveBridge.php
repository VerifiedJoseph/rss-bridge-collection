<?php
class TzAnnounceArchiveBridge extends BridgeAbstract {
	const NAME = 'IANA Time Zone Database Bridge';
	const URI = 'https://mm.icann.org/pipermail/tz-announce/';
	const DESCRIPTION = 'Returns Time Zone Database Mailing List';
	const MAINTAINER = 'VerifiedJoseph';

	const CACHE_TIMEOUT = 3600;
	
	public function collectData() {
		$item = array();
			
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request: ' . self::URI);
		
		$table = $html->find('table', 0);

		foreach($table->find('tr') as $row) {
	
			if ($row->children(0)->plaintext === 'Archive') { // skip header row
				continue;
			}
	
			$item['title'] = substr($row->children(0)->plaintext, 0, -1);
			$item['timestamp'] = strtotime(substr($row->children(0)->plaintext, 0, -1));
			$item['uri'] = self::URI . $row->children(1)->children(0)->href;
			$item['content'] = '<a href="' . $item['uri'] . '">' . $item['uri'] . '</a>';
			
			$this->items[] = $item;
		}
	}
}
