<?php
class ViceBridge extends BridgeAbstract {

	const NAME = 'Vice.com Topic Bridge';
	const URI = 'https://vice.com';
	const DESCRIPTION = 'Returns the newest posts for a topic from multiple vice.com editions.';
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

	private $cacheFolder = 'ViceBridgeCache';
	private $cacheFilename = null; 
	
	public function collectData() {

		if (!is_null($this->getInput('editions'))) {

			$this->editions = preg_split('/[\s,]+/', $this->getInput('editions'));

		} else {

			$this->editions[] = $this->defaultEdition;

		}

		$servedPosts = $this->loadCache();
		
		foreach ($this->editions as $edition) {

			$url = $this->getURI() . '/' . $edition . '/rss/topic/' . $this->getInput('topic');

			$feed = getContents($url)
			or returnServerError('Could not request: ' . $url);

			$xml = simplexml_load_string($feed);

			foreach ($xml->channel->item as $feedItem) {
				$item = array();

				$guid = (string)$feedItem->guid;

				$guid_sha1 = sha1($guid);
				
				if (isset($servedPosts['posts'][$guid_sha1])) { // Post is in cache.
					
					// Post is not same edition as the first served version, skip it.
					if ($servedPosts['posts'][$guid_sha1]['edition'] != $edition) {
						continue;
					}
					
				} else { // Post is not in cache, add it.
					$servedPosts['posts'][$guid_sha1]['edition'] = $edition;
				}

				$item['title'] = (string)$feedItem->title;
				$item['content'] = (string)$feedItem->children('content', true);
				$item['timestamp'] = strtotime((string)$feedItem->pubDate);
				$item['categories'] = (array)$feedItem->category;
				$item['uid'] = $guid;
				$item['uri'] = (string)$feedItem->link;
				$item['enclosures'] = array((string)$feedItem->enclosure['url']);

				$a = (array)$feedItem->children('dc', true);
				
				if (is_array($a['creator'])) {
					$item['author'] = implode(', ', $a['creator']);
				
				} else {

					$item['author'] = $a['creator'];
				
				}

				$this->items[] = $item;
			}
		}
		$this->orderItems();

		$this->saveCache($servedPosts);
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
	
	private function loadCache() {

		if (is_dir($this->cacheFolder) === false) {
			mkdir($this->cacheFolder, 0700);
		}

		$path = $this->cacheFolder . '/' . $this->cacheName();
		$handle = fopen($path, 'r');
		
		if ($handle != false) {
		
			$contents = fread($handle, filesize($path));
			fclose($handle);

			return json_decode($contents, true);
		}

		return array(
			'posts' => array()
		);

	}

	private function saveCache($contents) {

		$contents = json_encode($contents);

		$path = $this->cacheFolder . '/' . $this->cacheName();
		$handle = fopen($path, 'w');

		if ($handle != false) {
			fwrite($handle, $contents);
			fclose($handle);	
		}
	}
	
	private function cacheName() {

		if (is_null($this->cacheFilename)) {
			$this->cacheFilename = hash('sha256', $this->getInput('topic') . $this->getInput('editions')) . '.cache';
		}

		return $this->cacheFilename;

	}
	
}
