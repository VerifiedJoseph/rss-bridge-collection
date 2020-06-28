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

		$this->feedName = $html->find('div[class="br-masthead__title"]', 0)->plaintext;

		$results = $html->find('div.br-box-page.programmes-page', 0);
		foreach($results->find('div.programme.programme--tv') as $index => $div) {
			$item = array();

			$programmeId = $div->attr['data-pid'];
			$programmeTitle = $div->find('h2[class="programme__titles"]', 0)->plaintext;
			$programmePath = 'https://www.bbc.co.uk/iplayer/episode/' . $programmeId;

			$programmePage = getSimpleHTMLDOMCached($programmePath, 3600)
				or returnServerError('Could not request: ' . $programmePath);

			$image = $programmePage->find('meta[property="og:image"]', 0)->content;

			$json = $this->extractJson($programmePage);

			foreach ($json->episode->synopses as $size => $synopsis) {

				if ($size === 'large') {
					$description = nl2br($synopsis);
					break;
				}

				if ($size === 'medium') {
					$description = nl2br($synopsis);
					break;
				}

				$description = $synopse;
			}

			$duration = $json->versions[0]->duration->text;
			$date = $json->versions[0]->firstBroadcast;
			$availability = $json->versions[0]->availability->remaining->text;

			$item['uri'] = $programmePath;
			$item['title'] = $programmeTitle . '(' . $duration . ')';

			$item['content'] = <<<EOD
<a title="Watch on iPlayer" href="{$programmePath}"><img src="{$image}"></a><hr>Published: {$date} - Duration: {$duration} - {$availability}<hr>{$description}<br/>
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
		$data = substr($data, 0, -1);;

		$data = json_decode($data);

		if ($data === false) {
			returnServerError('Failed to decode extracted data');
		}

		return $data;
	}
}
