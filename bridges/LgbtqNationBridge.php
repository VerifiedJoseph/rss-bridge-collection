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

	protected function parseItem($item) {
		$item = parent::parseItem($item);

		$articleHtml = getSimpleHTMLDOMCached($item['uri'], 3600)
			or returnServerError('Could not request: ' . $item['uri']);

		$content = $articleHtml->find('div.single-body.entry-content', 0);
		
		foreach ($content->find('script') as $script) {
			$script->outertext = '';
		}

		$imageSrc = $articleHtml->find('div.single-entry-thumb > img', 0)->src;
		$imageSrc = str_replace(parse_url($imageSrc, PHP_URL_QUERY), '', $imageSrc);

		$item['content'] = $content->innertext;
		$item['enclosures'][] = $imageSrc;

		if ($articleHtml->find('div.entry-categories.col-sm-4 > a', 0)) {
			$item['categories'][] = $articleHtml->find('div.entry-categories.col-sm-4 > a', 0)->plaintext;	
		}

		foreach ($articleHtml->find('div.entry-tags.col-sm-8 > a') as $a) {
			$item['categories'][] = $a->plaintext;
		}

		return $item;
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
				return self::URI . '/author/' . $this->getInput('t');
			default: return parent::getURI();
		}
	}
}
