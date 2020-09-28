<?php
class ArchiveTeamTrackerBridge extends BridgeAbstract {
	const NAME = 'Archive Team Tracker Bridge';
	const URI = 'https://tracker.archiveteam.org/';
	const URI_JSON = 'https://warriorhq.archiveteam.org/projects.json';
	const DESCRIPTION = 'Returns archive team warrior projects';
	const MAINTAINER = 'VerifiedJoseph';

	const CACHE_TIMEOUT = 900;

	public function collectData() {
		$json = getContents(self::URI_JSON)
			or returnServerError('Could not request: ' . self::URI_JSON);

		$data = json_decode($json);

		foreach ($data->projects as $project) {
			$item = array();
			$item['title'] = $project->title;

			if (isset($project->leaderboard)) {
				$item['uri'] = $project->leaderboard;	
			}

			$item['enclosures'][] = $project->logo;
			$item['content'] = <<<EOD
<p>{$project->description}</p>
Repository: <a href="{$project->repository}">{$project->repository}</a>
EOD;

			$this->items[] = $item;	
		}
	}
}
