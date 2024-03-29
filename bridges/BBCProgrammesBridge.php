<?php
class BBCProgrammesBridge extends BridgeAbstract {
	const NAME = 'BBC Programmes Bridge';
	const URI = 'https://www.bbc.co.uk/programmes';
	const DESCRIPTION = 'Returns programme episodes available on iPlayer';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
			'id' => array(
				'name' => 'Programme ID',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'b006t14n',
			)
		)
	);

	const CACHE_TIMEOUT = 1800; // 30 mins

	private $feedName = '';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$this->feedName = trim($html->find('div[class="br-masthead__title"]', 0)->plaintext);

		foreach($html->find('ol.highlight-box-wrapper > div') as $index => $div) {
			$item = array();

			$programmeId = $div->attr['data-pid'];
			$programmeTitle = $div->find('h2[class="programme__titles"]', 0)->plaintext;
			$programmePath = 'https://www.bbc.co.uk/iplayer/episode/' . $programmeId;

			$programmePage = getSimpleHTMLDOMCached($programmePath, 3600)
				or returnServerError('Could not request: ' . $programmePath);

			$image = $programmePage->find('meta[property="og:image"]', 0)->content;

			$json = $this->extractJson($programmePage);

			$description = $this->getSynopsis($json);
			$duration = $json->versions[0]->duration->text;
			$date = $json->versions[0]->firstBroadcast;
			$availability = $json->versions[0]->availability->remaining->text;

			$item['uri'] = $programmePath;
			$item['title'] = $json->episode->subtitle . ' (' . $duration . ')';

			$item['content'] = <<<EOD
<a title="Watch on iPlayer" href="{$programmePath}"><img src="{$image}"></a><hr>Published: {$date} - Duration: {$duration} - {$availability}<hr><p>{$description}</p>
EOD;

			$item['timestamp'] = $date;
			$item['enclosures'][] = $image;

			$this->items[] = $item;

			if (count($this->items) >= 10) {
				break;
			}
		}
	}

	public function getURI() {

		if (!is_null($this->getInput('id'))) {
			return self::URI . '/' . $this->getInput('id') . '/episodes/player';
		}

		return parent::getURI();
	}

	public function getName() {

		if ($this->feedName) {
			return $this->feedName . ' - BBC';
		}

		return parent::getName();
	}

	private function extractJson($html) {
		$data = $html->find('script#tvip-script-app-store', 0)->innertext;
		$data = str_replace('window.__IPLAYER_REDUX_STATE__ = ', '', $data);
		$data = substr($data, 0, -1);

		$data = json_decode($data);

		if ($data === false) {
			returnServerError('Failed to decode extracted data');
		}

		return $data;
	}

	private function getSynopsis($json) {
		$sizes = array('large', 'medium' , 'small', 'editorial');
	
		foreach ($sizes as $size) {
			if(isset($json->episode->synopses->{$size})) {
				return nl2br($json->episode->synopses->{$size});
			}
		}

		return '';
	}
}
