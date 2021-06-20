<?php
class TheDailyBeastBridge extends BridgeAbstract {
	const NAME = 'The Daily Beast Bridge';
	const URI = 'https://www.thedailybeast.com';
	const DESCRIPTION = 'Returns newest articles (summary only)';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'By Category' => array(
			'c' => array(
				'name' => 'Category',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'us-news',
			),
		),
		'By Franchise' => array(
			'f' => array(
				'name' => 'franchise',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'coronavirus',
			),
		),
		'By Keyword' => array(
			'k' => array(
				'name' => 'Keyword',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'impeachment',
			),
		),
		'By Author' => array(
			'a' => array(
				'name' => 'Author',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'donald-kirk',
			),
		),
	);

	const CACHE_TIMEOUT = 3600; // 1 hour

	private $categoryUrlRegex = '/thedailybeast\.com\/category\/([\w-]+)/';
	private $franchiseUrlRegex = '/thedailybeast\.com\/franchise\/([\w-]+)/';
	private $keywordUrlRegex = '/thedailybeast\.com\/keyword\/([\w-]+)/';
	private $authorUrlRegex = '/thedailybeast\.com\/author\/([\w-]+)/';

	private $feedName = '';

	public function detectParameters($url) {
		$params = array();

		if(preg_match($this->categoryUrlRegex, $url, $matches)) {
			$params['context'] = 'By Category';
			$params['c'] = $matches[1];
			return $params;
		}

		if(preg_match($this->franchiseUrlRegex, $url, $matches)) {
			$params['context'] = 'By Franchise';
			$params['f'] = $matches[1];
			return $params;
		}

		if(preg_match($this->keywordUrlRegex, $url, $matches)) {
			$params['context'] = 'By Keyword';
			$params['k'] = $matches[1];
			return $params;
		}

		if(preg_match($this->authorUrlRegex, $url, $matches)) {
			$params['context'] = 'By Author';
			$params['a'] = $matches[1];
			return $params;
		}

		return null;
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		if ($html->find('h2.WrapHeader__title', 0)) {
			$this->feedName = $html->find('h2.WrapHeader__title', 0)->plaintext;
		}

		if ($html->find('h4.Byline__name', 0)) {
			$this->feedName = $html->find('h4.Byline__name', 0)->plaintext;
		}

		foreach ($html->find('article') as $article) {
			$item = array();

			$item['uri'] = $article->find('a', 0)->href;

			$articleHtml = getSimpleHTMLDOMCached($item['uri'], 7200)
				or returnServerError('Could not request: ' . $item['uri']);

			$item['title'] = $articleHtml->find('meta[property="og:title"]', 0)->content;

			if ($articleHtml->find('script[type="application/ld+json"]', 0)) {
				$schemaData = json_decode($articleHtml->find('script[type="application/ld+json"]', 0)->innertext);

				if (isset($schemaData->isAccessibleForFree)) {
					$item['title'] .= ' [Paywall]';
				}
			}

			$item['author'] = $articleHtml->find('meta[name="authors"]', 0)->content;
			$item['timestamp'] = $articleHtml->find('meta[property="article:published_time"]', 0)->content;

			$description = $articleHtml->find('meta[property="og:description"]', 0)->content;
			$image = $articleHtml->find('meta[property="og:image"]', 0)->content;

			$item['content'] = <<<EOD
<p><img src="{$image}"></p>
<p>{$description}<p><a href="{$item['uri']}">Read on thedailybeast.com</a>
EOD;

			$categories = explode(',', $articleHtml->find('meta[name="keywords"]', 0)->content);
			$item['categories'] = array_map('trim', $categories);
			$item['enclosures'][] = $image;

			$this->items[] = $item;
		}
	}

	public function getURI() {
		switch($this->queriedContext) {
			case 'By Category':
				return self::URI . '/category/' . $this->getInput('c');
			case 'By Franchise':
				return self::URI . '/franchise/' . $this->getInput('f');
			case 'By Keyword':
				return self::URI . '/keyword/' . $this->getInput('k');
			case 'By Author':
				return self::URI . '/author/' . $this->getInput('a');
			default: return parent::getURI();
		}
	}

	public function getName() {
		if (!empty($this->feedName)) {
			return $this->feedName . ' - The Daily Beast';
		}

		return parent::getName();
	}
}
