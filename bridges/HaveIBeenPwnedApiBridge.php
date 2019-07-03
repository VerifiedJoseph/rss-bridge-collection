<?php
class HaveIBeenPwnedApiBridge extends BridgeAbstract {
	const NAME = 'Have I Been Pwned (HIBP) API Bridge';
	const URI = 'https://haveibeenpwned.com';
	const DESCRIPTION = 'Returns list of Pwned websites for an email addrees';
	const MAINTAINER = 'VerifiedJoseph';

	const PARAMETERS = array(
		'Pwned websites' => array(),
		'Pwned Account' => array(
			'email' => array(
				'name' => 'Email address',
				'type' => 'text',
				'required' => true,
				'title' => 'Email address',
			),
		),
		'global' => array(
			'order' => array(
				'name' => 'Order by',
				'type' => 'list',
				'values' => array(
					'Breach date' => 'breachDate',
					'Date added to HIBP' => 'dateAdded',
				),
				'defaultValue' => 'dateAdded',
			)
		)
	);

	const CACHE_TIMEOUT = 3600;

	private $breaches = array();
	private $feedName = 'Have I Been Pwned';

	public function collectData() {

		if ($this->queriedContext === 'Pwned websites') {
			$this->feedName .= ' - Pwned Websites';

			$json = getContents(self::URI . '/api/v2/breaches');

			if($json === false)
				returnServerError('Could not request: ' . self::URI . '/api/v2/breaches');

			$this->handleJson($json);
			$this->orderBreaches();
			$this->createItems();
		}

		if ($this->queriedContext === 'Pwned Account') {
			$this->feedName .= ' - Pwned Account - ' . $this->getInput('email');

			$json = getContents(self::URI . '/api/v2/breachedaccount/' . $this->getInput('email'));

			if($json === false)
				returnServerError('Could not request: ' . self::URI . '/api/v2/breachedaccount/' . $this->getInput('email'));

			$this->handleJson($json);
			$this->orderBreaches();
			$this->createItems();
		}
	}

	public function getName() {

		if ($this->feedName) {
			return $this->feedName;
		}

		return parent::getName();
	}

	/**
	 * Handle JSOn returned by API, create breaches array
	 */
	private function handleJson(string $json) {

		$breaches = json_decode($json, true);

		foreach($breaches as $breach) {
			$item['title'] = $breach['Title'] . ' - ' . number_format($breach['PwnCount']) . ' breached accounts';
			$item['dateAdded'] = strtotime($breach['AddedDate']);
			$item['breachDate'] = strtotime($breach['BreachDate']);
			$item['uri'] = self::URI . '/PwnedWebsites#' . $breach['Name'];

			$item['content'] = '<p>' . $breach['Description'] . '<p>';
			$item['content'] .= '<p>Breach date:<br>' . date('d F Y', $item['breachDate']) . '</p>';
			$item['content'] .= '<p>Date added to HIBP:<br>' . date('d F Y', $item['dateAdded']) . '</p>';
			$item['content'] .= '<p>Compromised accounts:<br>' . number_format($breach['PwnCount']) . '</p>';
			$item['content'] .= '<p>Compromised data:<br>' . implode(', ', $breach['DataClasses']) . '</p>';

			$this->breaches[] = $item;
		}
	}

	/**
	 * Order Breaches by date added or date breached
	 */
	private function orderBreaches() {

		$sortBy = $this->getInput('order');
		$sort = array();

		foreach ($this->breaches as $key => $item) {
			$sort[$key] = $item[$sortBy];
		}

		array_multisort($sort, SORT_DESC, $this->breaches);

	}

	/**
	 * Create items from breaches array
	 */
	private function createItems() {

		foreach ($this->breaches as $breach) {
			$item = array();

			$item['title'] = $breach['title'];
			$item['timestamp'] = $breach[$this->getInput('order')];
			$item['uri'] = $breach['uri'];
			$item['content'] = $breach['content'];

			$this->items[] = $item;
		}
	}
}
