<?php
class LgbtqNationBridge extends FeedExpander {

	const NAME = 'LGBTQ Nation Bridge';
	const URI = 'https://www.lgbtqnation.com';
	const DESCRIPTION = 'Returns newest articles by category, tag or author';
	const MAINTAINER = 'VerifiedJoseph';

	const PARAMETERS = array(
		'Newest Articles' => array(),
		'By Category' => array(
			'c' => array(
				'name' => 'Category',
				'required' => true,
				'exampleValue' => 'pride'
			)
		),
		'By Tag' => array(
			't' => array(
				'name' => 'Tag',
				'required' => true,
				'exampleValue' => 'megan-rapinoe'
			)
		),
		'By Author' => array(
			'a' => array(
				'name' => 'Author',
				'required' => true,
				'exampleValue' => 'bil-browning'
			)
		)
	);

	const CACHE_TIMEOUT = 1800; // 30 minutes

	private $categoryUrlRegex = '/lgbtqnation\.com\/([\w-]+)\//';
	private $tagUrlRegex = '/lgbtqnation\.com\/tag\/([\w-]+)/';
	private $authorUrlRegex = '/lgbtqnation\.com\/author\/([\w-]+)/';

	public function detectParameters($url) {
		$params = array();

		if(preg_match($this->tagUrlRegex, $url, $matches)) {
			$params['context'] = 'By Tag';
			$params['t'] = $matches[1];
			return $params;
		}

		if(preg_match($this->authorUrlRegex, $url, $matches)) {
			$params['context'] = 'By Author';
			$params['a'] = $matches[1];
			return $params;
		}

		if(preg_match($this->categoryUrlRegex, $url, $matches)) {
			$params['context'] = 'By Category';
			$params['c'] = $matches[1];
			return $params;
		}

		return null;
	}

	public function collectData() {
		$this->collectExpandableDatas($this->getURI() . '/feed/', 10);
	}

	public function getURI() {
		switch($this->queriedContext) {
			case 'By Category': 
				return self::URI . '/' . $this->getInput('c');
			case 'By Tag': 
				return self::URI . '/tag/' . $this->getInput('t');
			case 'By Author': 
				return self::URI . '/author/' . $this->getInput('a');
			default: return parent::getURI();
		}
	}

	protected function parseItem($item) {
		$item = parent::parseItem($item);

		$html = getSimpleHTMLDOMCached($item['uri'], 7200);
		$content = $html->find('div.single-body.entry-content', 0);

		// Skip pages missing entry-content
		if (is_null($html->find('div.single-body.entry-content', 0))) {
			return array();
		}

		// Remove newsletter signup
		if ($html->find('div.sidebar-newsletter-prompt', 0)) {
			$html->find('div.sidebar-newsletter-prompt', 0)->outertext = '';
		}

		// Remove related stories
		if ($html->find('div.wp-block-related-links', 0)) {
			$html->find('div.wp-block-related-links', 0)->outertext = '';
		}

		foreach ($content->find('script') as $script) {
			$script->outertext = '';
		}

		$item['content'] = $content;
		$item['enclosures'][] = $html->find('meta[property="og:image"]', 0)->content;

		if ($html->find('meta[property="article:tags"]', 0)) {
			$item['categories'] = array_map('trim', 
				explode(',', $html->find('meta[property="article:tags"]', 0)->content)
			);
		}

		return $item;
	}
}
