<?php
class TheDailyBeastBridge extends BridgeAbstract {
	const NAME = 'The Daily Beast Bridge';
	const URI = 'https://www.thedailybeast.com';
	const DESCRIPTION = 'Returns newest articles by category or keyword';
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
		'By Keyword' => array(
			'k' => array(
				'name' => 'Keyword',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'lgbt',
			),
		),
	);

	const CACHE_TIMEOUT = 3600; // 1 hour

	private $feedName = '';

	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$this->feedName = $html->find('h2.WrapHeader__title', 0)->plaintext;

		foreach ($html->find('article') as $article) {
			$item = array();

			$item['uri'] = $article->find('a', 0)->href;

			$articleHtml = getSimpleHTMLDOMCached($item['uri'], 3600)
				or returnServerError('Could not request: ' . $item['uri']);

			$item['title'] = $articleHtml->find('meta[property="og:title"]', 0)->content;

			if ($articleHtml->find('script[type="application/ld+json"]', 0)) {
				$SchemaData = json_decode($articleHtml->find('script[type="application/ld+json"]', 0)->innertext);

				if (isset($SchemaData->isAccessibleForFree)) {
					$item['title'] .= ' [Paywall]';
				}
			}

			/*if ($articleHtml->find('article.members-only', 0)) {
				$item['title'] .= ' [Paywall]';
			}*/

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
			case 'By Keyword':
				return self::URI . '/keyword/' . $this->getInput('k');
			default: return parent::getURI();
		}
	}

	public function getName() {

		if (!empty($this->feedName)) {
			return $this->feedName . ' - The Daily Beast';
		}

		return parent::getName();
	}

	public function getIcon() {
		return 'https://www.thedailybeast.com/static/b30a79ed230b726a0470067d49631937.ico';
	}
}
