<?php
class GitHubDesktopBridge extends BridgeAbstract {
	const NAME = 'GitHub Desktop Bridge';
	const URI = 'https://desktop.github.com/release-notes/';
	const DESCRIPTION = 'Returns release notes for GitHub Desktop';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 3600; // 1 hour

	private $jsonURl = 'https://central.github.com/deployments/desktop/desktop/changelog.json';

	public function collectData() {
		$json = getContents($this->jsonURl)
			or returnServerError('Could not request: ' . $this->jsonURl);

		$data = json_decode($json);

		foreach ($data as $release) {
			$item = array();
			$item['title'] = $release->version;
			$item['timestamp'] = $release->pub_date;

			$item['content'] = '<ul>';
			foreach ($release->notes as $note) {
				$item['content'] .= '<li>' . $this->createLinks($note) . '</li>';
			}
			$item['content'] .= '</ul>';

			$this->items[] = $item;
		}
	}

	private function createLinks($note) {
		return preg_replace(
			'/(?:#([0-9]+))/ims', 
			'<a target="_blank" href="https://github.com/desktop/desktop/issues/$1" target="_blank">#$1</a> ',
			$note
		);
	}
}
