<?php
/*
	This bridge uses a custom cache system that tracks articles via their unique ID.
	The unique ID prevents the same article appearing more than once if it is returned by multiple edition feeds.

	The custom cache files are saved in a folder called 'ViceBridgeCache' in the main rss-bridge folder
	The name and location of the cache folder can be changed by modifying the '$cacheFolder' variable.
*/
class ViceTopicBridge extends BridgeAbstract {
	const NAME = 'Vice.com Topic Bridge';
	const URI = 'https://vice.com';
	const DESCRIPTION = 'Returns the newest articles for a topic from multiple vice.com editions.';
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
Supported values: en_us, en_uk, en_ca, en_asia, en_au, en_in, fr_ca, ro, rs, es de_ch,
es_latam, de_at, be, pt_br, fr, fr_be, de, gr, id_id, it, jp, nl, pt, ar',
				'required' => true
			)
		)
	);

	const CACHE_TIMEOUT = 1800; // 30 minutes

	private $editions = array();
	private $supportedEditions = array(
		'en_us',
		'en_uk',
		'en_ca',
		'en_asia',
		'en_au',
		'en_in',
		'fr_ca',
		'ro',
		'rs',
		'es',
		'de_ch',
		'es_latam',
		'de_at',
		'be',
		'pt_br',
		'fr',
		'fr_be',
		'de',
		'gr',
		'id_id',
		'it',
		'jp',
		'nl',
		'pt',
		'ar'
	);

	private $cacheFolder = 'ViceBridgeCache';
	private $cacheFilename = null;
	private $cache = array(
		'posts' => array()
	);

	public function collectData() {
		$this->editions = explode(',', $this->getInput('editions'));
		$this->editions = array_map('trim', $this->editions);
		$this->editions = array_unique($this->editions);

		$this->loadCache();

		foreach ($this->editions as $edition) {

			if (!in_array($edition, $this->supportedEditions)) {
				returnServerError('Unsupport edition value: ' . $edition);
			}

			$url = $this->getURI() . '/' . $edition . '/rss/topic/' . $this->getInput('topic');

			$feed = getContents($url)
				or returnServerError('Could not request: ' . $url);

			$xml = new SimpleXMLElement($feed);

			foreach ($xml->channel->item as $feedItem) {
				$ns = $feedItem->getNamespaces(true);
				$item = array();

				$guid = (string)$feedItem->guid;

				// Article in cache, skip this version from a different edition.
				if ($this->articleInCache($guid, $edition)) {
					continue;
				}

				$this->addToCache($guid, $edition);

				$item['title'] = (string)$feedItem->title;
				$item['content'] = (string)$feedItem->children($ns['content']);
				$item['timestamp'] = strtotime((string)$feedItem->pubDate);
				$item['categories'][] = $edition;

				foreach ($feedItem->category as $category) {
					$item['categories'][] = htmlspecialchars($category, ENT_QUOTES);
				}

				$item['uid'] = $guid;
				$item['uri'] = (string)$feedItem->link;
				$item['enclosures'] = array((string)$feedItem->enclosure['url']);

				$author = (array)$feedItem->children($ns['dc'])->creator;

				if (is_array($author)) {
					$item['author'] = implode(', ', array_unique($author));
				}

				if (is_string($author)) {
					$item['author'] = $a;
				}

				$this->items[] = $item;
			}
		}

		$this->orderItems();
		$this->saveCache();
	}

	public function getName() {

		if (!is_null($this->getInput('topic'))) {
			return $this->getInput('topic') . ' - Vice.com (' . implode(', ', $this->editions) . ')';
		}

		return parent::getName();
	}

	private function orderItems() {
		$sort = array();

		foreach ($this->items as $key => $item) {
			$sort[$key] = $item['timestamp'];
		}

		array_multisort($sort, SORT_DESC, $this->items);
	}

	private function loadCache() {

		if (is_dir($this->cacheFolder) === false) {
			mkdir($this->cacheFolder, 0775);
		}

		$path = $this->cacheFolder . '/' . $this->cacheName();

		if (file_exists($path)) {
			$handle = fopen($path, 'r');

			if ($handle != false) {
				$contents = fread($handle, filesize($path));
				fclose($handle);

				$this->cache = json_decode($contents, true);
			}
		}
	}

	private function saveCache() {
		$contents = json_encode($this->cache);

		$path = $this->cacheFolder . '/' . $this->cacheName();
		$handle = fopen($path, 'w');

		if ($handle != false) {
			fwrite($handle, $contents);
			fclose($handle);
		}
	}

	private function articleInCache($id, $edition) {

		if (isset($this->cache['posts'][$id]) && $this->cache['posts'][$id]['edition'] !== $edition) {
			return true;
		}

		return false;
	}

	private function addToCache($id, $edition) {

		if (!isset($this->cache['posts'][$id])) {
			$this->cache['posts'][$id] = array(
				'edition' => $edition
			);
		}
	}

	private function cacheName() {

		if (is_null($this->cacheFilename)) {
			$this->cacheFilename = hash('sha256', $this->getInput('topic') . $this->getInput('editions')) . '.cache';
		}

		return $this->cacheFilename;
	}
}
