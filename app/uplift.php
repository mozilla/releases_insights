<?php

function getBugsFromHgWeb($query) :array
{
	$results    = getJson($query, sha1($query))['pushes'];
	$changesets = array_column($results, 'changesets');
	$uplifts    = [];
	$backouts   = [];

	foreach($changesets as $items) {
		foreach ($items as $subitem) {
			$subitem = explode("\n", $subitem['desc'])[0];
			$subitem = strtolower(mtrim($subitem));

			if (startsWith($subitem, ['no bug', 'automatic version bump'])) {
				continue;
			}

			if (inString($subitem, [
				'a=test-only', 'a=release', 'a=npotb', 'a=searchfox-only',
				'try-staging', 'taskcluster', 'a=tomprince', 'a=aki', 'a=testing'])) {
				continue;
			}

			if (startsWith($subitem, 'backed out')) {
				$backouts[] = $subitem;
				continue;
			}

			if (preg_match("/bug \d+/", $subitem, $matches)) {
				$uplifts[] = trim(str_replace('bug', '', $matches[0]));
			}
		}
	}

	$uplifts = array_unique($uplifts);

	$backed_out_bugs = [];
	foreach($backouts as $backout) {
		if (preg_match_all("/bug \d+/", $backout, $matches) !== false) {
			$matches = str_replace('bug ', '', $matches[0]);
			$backed_out_bugs = array_merge($backed_out_bugs, $matches);
		}
	}

	$backed_out_bugs= array_unique($backed_out_bugs);

	return ['uplifts' => $uplifts, 'backouts' => $backed_out_bugs];
}


