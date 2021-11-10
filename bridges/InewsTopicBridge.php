<?php
class InewsTopicBridge extends FeedExpander {
	const MAINTAINER = 'VerifiedJoseph';
	const NAME = 'I News Topic Bridge';
	const URI = 'https://inews.co.uk';
	const DESCRIPTION = 'Returns newest articles from multiple topics';
	const PARAMETERS = array(
		array(
			'topics' => array(
				'name' => 'Topics',
				'type' => 'text',
				'required' => true,
				'title' => 'Topics',
				'exampleValue' => 'Topic, Topic2'
			)
		)
	);

	protected function parseItem($item) {
		$itemContent = $item->children('http://purl.org/rss/1.0/modules/content/');
		$mediaContent = $item->children('http://search.yahoo.com/mrss/');

		$thumbnail = (string) $mediaContent->thumbnail->attributes()['url'];
		$content = (string) $itemContent->encoded;

		$categories = array();
		foreach ($item->category as $category) {
			$categories[] = (string) $category;
		}

		$item = parent::parseItem($item);
		$item['content'] = $this->editContent($content);
		$item['categories'] = $categories;
		$item['enclosures'][] = $thumbnail;

		return $item;
	}

	public function collectData() {
		$topics = explode(',', $this->getInput('topics'));

		foreach ($topics as $topic) {
			$this->collectExpandableDatas($this->getFeedURI($topic));
		}

		$this->removeDups();
		$this->orderItems();
	}

	public function getURI() {
		return self::URI;
	}

	public function getName() {
		if(is_null($this->getInput('topics')) === false) {
			return 'I News Topics: ' . $this->getInput('topics');
		}

		return parent::getName();
	}

	private function getFeedURI($topic) {
		return self::URI . '/topic/' . trim($topic) . '/rss';
	}

	private function removeDups() {
		$urls = array();
		$items = array();

		foreach ($this->items as $item) {
			if (in_array($item['uri'], $urls) === true) {
				continue;
			}

			$urls[] = $item['uri'];
			$items[] = $item;
		}

		$this->items = $items;
	}

	private function orderItems() {
		$sort = array();

		foreach ($this->items as $key => $item) {
			$sort[$key] = $item['timestamp'];
		}

		array_multisort($sort, SORT_DESC, $this->items);

	}

	private function editContent($content) {
		$html = str_get_html($content);

		foreach ($html->find('figure.inews__shortcode-readmore') as $figure) {
			$figure->find('div', 0)->outertext = '';
			$figure->find('h4', 0)->outertext = '';
			$figure->find('p', 0)->outertext = 'Read more: ' . $figure->find('a', 0);
		}

		return $html;
	}
}
