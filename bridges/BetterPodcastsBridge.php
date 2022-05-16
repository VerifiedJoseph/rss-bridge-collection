<?php
class BetterPodcastsBridge extends FeedExpander {
	const NAME = 'Better Podcasts Bridge';
	const URI = 'https://signal.org/blog/';
	const DESCRIPTION = 'Adds duration to item title';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		array(
			'feed' => array(
				'name' => 'Feed',
				'type' => 'text',
				'required' => true,
				'title' => 'Feed URL',
			)
		)
	);

	const CACHE_TIMEOUT = 1800; // 30mins

	protected function parseItem($item) {
		$itunesNodes = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');

		$duration = '';

		if (isset($itunesNodes->duration)) {
			$duration .= ' (' . $this->formatDuration($itunesNodes->duration) . ')';
		}

		$item = parent::parseItem($item);
		$item['title'] .= $duration;

		return $item;
	}

	public function collectData() {
		$this->collectExpandableDatas($this->getInput('feed'), 15);
	}

	private function  formatDuration($duration) {
		// Converts seconds to hour:minutes:seconds
		if (preg_match('/^([0-9]+)$/', $duration)) {
			if ($duration < 3600) {
				$duration = gmdate('i:s', (int)$duration);
			} else {
				$duration = gmdate('H:i:s', (int)$duration);
			}
		}
	
		// Only display minutes and seconds if duration is less than an hour.
		if (preg_match('/^00:([0-9:]+)/', $duration, $match)) {
			$duration = $match[1];
		}
	
		return $duration;
	}
}
