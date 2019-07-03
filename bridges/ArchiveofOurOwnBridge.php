<?php
class ArchiveofOurOwnBridge extends BridgeAbstract {
	const NAME = 'Archive of Our Own (AO3) Bridge';
	const URI = 'https://archiveofourown.org';
	const DESCRIPTION = 'Returns fanfiction by user, series, tag, or chapters.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'User Profile' => array(
			'u' => array(
				'name' => 'username',
				'type' => 'text',
				'required' => true,
				'title' => 'Username',
			),
			'c' => array(
				'name' => 'content',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Works' => 'works',
					'Series' => 'series',
					'Bookmarks' => 'bookmarks',
					'Gifts' => 'gifts'
				)
			),
			'l' => array(
				'name' => 'Limit number of tags',
				'type' => 'checkbox'
			)
		),
		'Series' => array(
			's' => array(
				'name' => 'Series ID',
				'type' => 'text',
				'required' => true,
				'title' => 'Series ID',
			),
			'l' => array(
				'name' => 'Limit number of tags',
				'type' => 'checkbox'
			)
		),
		'Tag' => array(
			't' => array(
				'name' => 'Tag',
				'type' => 'text',
				'required' => true,
				'title' => 'Tag',
			),
			'l' => array(
				'name' => 'Limit number of tags',
				'type' => 'checkbox'
			)
		),
		'Chapters' => array(
			'w' => array(
				'name' => 'Work ID',
				'type' => 'text',
				'required' => true,
				'title' => 'Work ID',
			),
		),
	);

	const CACHE_TIMEOUT = 3600;

	private $feedName = '';

	private $userProfile = array(
		'works' => array(
			'url' => '/works',
			'liClass' => 'li.work.blurb.group',
			'regex' => '/Works? by ([A-Za-z0-9_]+)$/'
		),
		'series' => array(
			'url' => '/series',
			'liClass' => 'li.series.blurb.group',
			'regex' => '/^([A-Za-z0-9_]+)\&#39;s Series$/'
		),
		'bookmarks' => array(
			'url' => '/bookmarks?bookmark_search[sort_column]=bookmarkable_date',
			'liClass' => 'li.bookmark.blurb.group',
			'regex' => '/Bookmarks? by ([A-Za-z0-9_]+)$/'
		),
		'collections' => array(
			'url' => '/collections',
			'liClass' => 'li.collections.blurb.group',
			'regex' => '/^([A-Za-z0-9_]+)\&#39;s Collections$/'
		),
		'gifts' => array(
			'url' => '/gifts',
			'liClass' => 'li.work.blurb.group',
			'regex' => '/^Gifts for ([A-Za-z0-9_]+)$/'
		),
	);

	private $currentWork;

	public function collectData() {
		$item = array();

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());
		
		// Feed for works, series, bookmarks or gifts from a user's profile.
		if ($this->queriedContext === 'User Profile') {

			$content_type = $this->getInput('c');

			// Feed name
			if(preg_match($this->userProfile[$content_type]['regex'], trim($html->find('h2.heading', 0)->plaintext), $matches)) {
				$this->feedName = $matches[1] . ' - ' . ucfirst($this->getInput('c'));
			}

			foreach($html->find($this->userProfile[$content_type]['liClass']) as $work) {
				$this->currentWork = $work;

				$item['title'] = $this->processMetadata('title');
				$item['author'] = $this->processMetadata('author');
				$item['timestamp'] = $this->processMetadata('timestamp');
				$item['uri'] = $this->processMetadata('uri');

				$item['content'] = $this->processContent();
				$item['content'] .= $this->processStats();
				$item['categories'] = $this->processTags();

				$this->items[] = $item;
			}
		}

		// Feed for works of a specific series.
		if ($this->queriedContext === 'Series') {

			$seriesTitle = $html->find('h2.heading', 0)->plaintext;
			$SeriesCreator = $html->find('dl.series.meta.group', 0)->children(1)->plaintext;
			$SeriesCreatorPath = self::URI . $html->find('dl.series.meta.group', 0)->children(1)->children(0)->href;

			$this->feedName = $seriesTitle . ' (Series By ' . $SeriesCreator . ')';

			foreach($html->find('li.work.blurb.group') as $work) {
				$this->currentWork = $work;

				$item['title'] = $this->processMetadata('title');
				$item['author'] = $this->processMetadata('author');
				$item['timestamp'] = $this->processMetadata('timestamp');
				$item['uri'] = $this->processMetadata('uri');

				$item['content'] = $this->processContent();
				$item['content'] .= $this->processStats();
				$item['categories'] = $this->processTags();

				$this->items[] = $item;
			}

			$this->fixDateOrder();
		}

		// Feed for works of a specific tag.
		if ($this->queriedContext === 'Tag') {

			$TagTitle = $html->find('h2.heading', 0)->children(0)->plaintext;

			$this->feedName = $TagTitle . ' - Tag';

			foreach($html->find('li.work.blurb.group') as $work) {
				$this->currentWork = $work;

				$item['title'] = $this->processMetadata('title');
				$item['author'] = $this->processMetadata('author');
				$item['timestamp'] = $this->processMetadata('timestamp');
				$item['uri'] = $this->processMetadata('uri');

				$item['content'] = $this->processContent();
				$item['categories'] = $this->processTags();

				$this->items[] = $item;
			}
		}

		// Feed for chapters of a specific work.
		if ($this->queriedContext === 'Chapters') {

			$heading = $html->find('h2.heading', 0);

			$workTitle = $heading->children(0)->plaintext;

			$authors = array();
			foreach($heading->find('a[rel=author]') as $a) {
				$authors[] = htmlspecialchars_decode($a->plaintext, ENT_QUOTES);
			}

			$workCreator = implode(', ', $authors);

			$this->feedName = $workTitle;

			foreach($html->find('ol.chapter.index.group li') as $chapter) {
				$date = str_replace(array('(', ')'), array(''), $chapter->children(1)->plaintext);

				$item['title'] = htmlspecialchars_decode($chapter->children(0)->plaintext, ENT_QUOTES);
				$item['author'] = $workCreator;

				$item['timestamp'] = strtotime($date);
				$item['uri'] = self::URI . $chapter->children(0)->href;

				$this->items[] = $item;
			}

			$this->items = array_reverse($this->items);
		}
	}

	public function getName() {

		if ($this->feedName) {
			return $this->feedName;
		}

		return parent::getName();
	}

	public function getURI() {
		
		switch($this->queriedContext) {
			case 'User Profile': 
				return self::URI . '/users/' . $this->getInput('u') 
					. $this->userProfile[$this->getInput('c')]['url'];
			case 'Series': 
				return self::URI . '/series/' 
					. $this->getInput('s'); 
			case 'Tag': 
				return self::URI . '/tags/' 
					. $this->getInput('t') . '/works'; 
			case 'Chapters': 
				return self::URI . '/works/' 
					. $this->getInput('w') . '/navigate'; 
			default: return parent::getURI();
		}
	}

	private function processMetadata($name) {

		$heading = $this->currentWork->find('h4.heading', 0);

		switch($name) {
			case 'title':

				return htmlspecialchars_decode($heading->find('a', 0)->plaintext, ENT_QUOTES);

			break;
			case 'author':

				$authors = array();

				foreach($heading->find('a[rel=author]') as $a) {
					$authors[] = htmlspecialchars_decode($a->plaintext, ENT_QUOTES);
				}

				return implode(', ', $authors);

			break;
			case 'timestamp':

				if ($this->getInput('c') === 'bookmarks') {
					return strtotime($this->currentWork->find('div.user.module.group > p.datetime', 0)->plaintext);
				}

				return strtotime($this->currentWork->find('p.datetime', 0)->plaintext);

			break;
			case 'uri':

				return self::URI . $heading->find('a', 0)->href;

			break;
			default: return null;
		}
	}

	private function processContent() {

		$content = '';
		$authors = '';
		$fandoms = '';

		// Description
		if ($this->currentWork->find('blockquote', 0)) {
			$content .= trim($this->currentWork->find('blockquote', 0)->innertext);
		}

		// Series
		if ($this->currentWork->find('ul.series', 0)) {
			$series = '';

			foreach($this->currentWork->find('ul.series li') as  $s) {
				$part = $s->children(0)->plaintext;
				$name = $s->children(1)->plaintext;
				$link = self::URI . $s->children(1)->href;
				$series .= '<br>Part ' . $part . ' of <a target="_blank" href="' . $link . '">' . $name . '</a>';
			}

			$content .= '<p>Series:' . $series . '</p>';
		}

		// Authors
		foreach($this->currentWork->find('h4.heading a[rel=author]') as $a) {
			$authors  .= '<br><a href="' . self::URI . $a->href . '">' . htmlspecialchars_decode($a->plaintext, ENT_QUOTES) . '</a>';
		}

		$content .= '<p>Authors:' . $authors . '</p>';

		// Fandoms
		foreach($this->currentWork->find('h5.fandoms.heading a') as $fandom) {
			$fandoms .= '<br><a target="_blank" href="' . self::URI . $fandom->href . '">' . $fandom->innertext . '</a>';
		}

		$content .= '<p>Fandoms:' . $fandoms . '</p>';

		return $content;
	}

	private function processTags() {

		$maxTags = 15;
		$tagCount = 0;
		$tags = array();

		foreach($this->currentWork->find('ul.tags li') as $tag) {
			if ($tag->class === 'warnings') {
				continue;
			}

			$tagCount++;

			$tags[] = htmlspecialchars_decode($tag->find('a', 0)->innertext, ENT_NOQUOTES);

			if ($this->getInput('l') && $tagCount === $maxTags) {
				break;
			}
		}

		return $tags;
	}

	private function processStats() {

		$noNumberFormatting = array('language','words', 'chapters');

		$stats = '';

		if (($this->queriedContext === 'User Profile' && $this->getInput('c') != 'series') || $this->queriedContext === 'Series') {

			$dl = $this->currentWork->find('dl', 0);

			foreach($dl->find('dd') as $stat) {
				$value = $stat->plaintext;

				if (in_array($stat->class, $noNumberFormatting) === false) {
					$value = number_format($stat->plaintext);
				}

				$stats .= '<p>' . ucfirst($stat->class) . ': <br>' . $value . '</p>';
			}
		}

		return $stats;
	}

	private function fixDateOrder() {

		$sort = array();

		foreach ($this->items as $key => $item) {
			$sort[$key] = $item['timestamp'];
		}

		array_multisort($sort, SORT_DESC, $this->items);
	}
}
