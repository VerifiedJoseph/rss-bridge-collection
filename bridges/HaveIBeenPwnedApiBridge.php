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

	public function collectData() {

		$url = self::URI;
		
		if ($this->queriedContext === 'Pwned websites') {
			$url .= '/api/v2/breaches';
		}

		if ($this->queriedContext === 'Pwned Account') {
			$url .= '/api/v2/breachedaccount/' . urlencode($this->getInput('email'));
		}
		
		$header = array(
			'User-Agent: Have I Been Pwned RSS-bridge'
		); 
		
		$json = getContents($url, $header) or
			returnServerError('Could not request: ' . $json);
		
		$this->handleJson($json);
		$this->orderBreaches();
		$this->createItems();

	}

	public function getName() {

		if ($this->queriedContext === 'Pwned websites') {
			return 'Pwned Websites - Have I Been Pwned';
		}

		if ($this->queriedContext === 'Pwned Account') {
			return $this->getInput('email') . ' - Pwned Account - Have I Been Pwned';	
		}

		return parent::getName();
	}
	
	public function getURI() {

		if ($this->queriedContext === 'Pwned websites') {
			return self::URI . '/PwnedWebsites';
		}

		return parent::getURI();
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

			$breachDate = date('d F Y', $item['breachDate']);
			$dateAdded = date('d F Y', $item['dateAdded']);
			$compromisedAccounts = number_format($breach['PwnCount']);
			$compromisedData = implode(', ', $breach['DataClasses']);
<p>{$breach['Description']}<p>
<p><strong>Breach date:</strong> {$breachDate}<br>
<strong>Date added to HIBP:</strong> {$dateAdded}<br>
<strong>Compromised accounts:</strong> {$compromisedAccounts}<br>
<strong>Compromised data:</strong> {$compromisedData}</p>
EOD;

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
