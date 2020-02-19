<?php
class TheGuardianCartoonsBridge extends FeedExpander {

	const NAME = 'The Guardian Cartoons Bridge';
	const URI = 'https://www.theguardian.com';
	const DESCRIPTION = 'Returns the newest cartoons from The Guardian.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
			'cartoon' => array(
				'name' => 'Cartoon',
				'type' => 'list',
				'values' => array(
					'All Cartoons' => 'cartoons/archive',
					'Guardian Opinion cartoon' => 'commentisfree/series/guardian-comment-cartoon',
					'Tom Gauld\'s cultural cartoons' => 'books/series/tom-gauld-s-cultural-cartoons',
					'Modern Toss' => 'culture/series/modern-toss',
					'Clare in the Community' => 'society/series/clareinthecommunity',
					'Loomus' => 'lifeandstyle/series/loomus',
					'Steve Bell\'s If...' => 'commentisfree/series/if',
					'Berger & Wyse' => 'lifeandstyle/series/berger-wyse',
					'First Dog on the Moon' => 'profile/first-dog-on-the-moon',
					'The Stephen Collins cartoon' => 'lifeandstyle/series/the-stephen-collins-cartoon',
					'The Simone Lia cartoon' => 'culture/series/the-simone-lia-cartoon',
				),
			)
		)
	);

	const CACHE_TIMEOUT = 3600; // 1 hour

	protected function parseItem($item) {
		$item = parent::parseItem($item);

		$articleHtml = getSimpleHTMLDOMCached($item['uri'])
			or returnServerError('Could not request: ' . $item['uri']);

		if ($articleHtml->find('picture', 0)) {
			$picture = $articleHtml->find('picture', 0);
			$explodeParts = explode(' ', $picture->find('source', 0)->attr['srcset']);
			$imageUrl = $explodeParts[0];

		} else if ($articleHtml->find('figure', 0)) {
			$figure = $articleHtml->find('figure', 0);

			preg_match('/srcs-desktop=(.*)&amp;/', $figure->attr['data-canonical-url'], $match)
				or returnServerError('Could not extract details');

			$explodeParts = explode('&amp;', $match[1]);
			$imageUrl = $explodeParts[0];
		}

		$description = $articleHtml->find('meta[name="description"]', 0)->content;

		$item['content'] = <<<EOD
<img src="{$imageUrl}"><p>{$description}</p>
EOD;

		// Get categories
		if ($articleHtml->find('meta[name="keywords"]', 0)) {
			$categories = explode(',', $articleHtml->find('meta[name="keywords"]', 0)->content);
			$item['categories'] = array_map('trim', $categories);	
		}

		$item['enclosures'][] = $imageUrl;

		return $item;
	}

	public function collectData() {
		$this->collectExpandableDatas($this->getURI() . '/rss', 10);
	}

	public function getURI() {

		if (!is_null($this->getInput('cartoon'))) {
			return self::URI . '/' . $this->getInput('cartoon');
		}

		return parent::getURI();
	}

	public function getName() {

		if (!is_null($this->getInput('cartoon'))) {
			$parameters = $this->getParameters();

			$contentValues = array_flip($parameters[0]['cartoon']['values']);

			return $contentValues[$this->getInput('cartoon')] . ' - The Guardian';
		}

		return parent::getName();
	}
}
