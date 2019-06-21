<?php
class ViceTopicBridge extends BridgeAbstract {

	const NAME = 'Vice.com Topic Bridge';
	const URI = 'https://vice.com';
	const DESCRIPTION = 'Returns the newest posts from a topic across vice.com editions.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
			'topic' => array(
				'name' => 'Topic',
				'type' => 'text',
				'exampleValue' => 'lgbtq',
				'required' => true
			),
			'editions' => array(
				'name' => 'editions',
				'type' => 'text',
				'exampleValue' => 'en_us, en_uk',
				'title' => 'A comma separated list of vice.com editions. 
Supported values: en_us, en_uk, en_ca, en_asia, en_au',
				'required' => true
			)
		)
	);

	const CACHE_TIMEOUT = 3600; // 1 hour

	private $defaultEdition = 'en_us';
	private $editions = array();
	private $guids = array();

	public function collectData() {

		if (!is_null($this->getInput('editions'))) {

			$this->editions = preg_split('/[\s,]+/', $this->getInput('editions'));

		} else {

			$this->editions[] = $this->defaultEdition;

		}

		foreach ($this->editions as $edition) {

			$url = $this->getURI() . '/' . $edition . '/rss/topic/' . $this->getInput('topic');

			$feed = getContents($url)
			or returnServerError('Could not request: ' . $url);

			$xml = simplexml_load_string($feed);

			foreach ($xml->channel->item as $feedItem) {
				$item = array();

				$guid = (string)$feedItem->guid;

				if (in_array($guid, $this->guids)) {
					continue;
				}

				$this->guids[] = $guid;

				$item['title'] = (string)$feedItem->title;
				$item['content'] = (string)$feedItem->children('content', true);
				$item['timestamp'] = strtotime((string)$feedItem->pubDate);
				$item['categories'] = (array)$feedItem->category;
				$item['uid'] = $guid;
				$item['uri'] = (string)$feedItem->link;
				$item['enclosures'] = array((string)$feedItem->enclosure['url']);

				$a = (array)$feedItem->children('dc', true);
				$item['author'] = implode(', ', $a['creator']);

				$this->items[] = $item;
			}
		}
		$this->orderItems();
	}

	private function orderItems() {

		$sort = array();

		foreach ($this->items as $key => $item) {
			$sort[$key] = $item['timestamp'];
		}

		array_multisort($sort, SORT_DESC, $this->items);

	}

	public function getName() {

		if (!is_null($this->getInput('topic'))) {

			return $this->getInput('topic') . ' - Vice.com (' . implode(', ', $this->editions) . ')';
		}

		return parent::getName();
	}
}
