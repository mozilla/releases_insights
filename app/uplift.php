<?php

function getBugsFromHgWeb($query) :array
{
	$results    = getJson($query)['pushes'];
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

			// Commits can be ignored if they contain one of these strings
			if (inString($subitem, [
				'a=test-only', 'a=release', 'a=npotb', 'a=searchfox-only',
				'try-staging', 'taskcluster', 'a=tomprince', 'a=aki', 'a=testing',
				'[mozharness]', 'r=aki', 'r=tomprince', 'r=mtabara', 'a=jorgk'
			])) {
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


	// Substract uplifts that were backed out later
	$clean_uplifts = array_diff($uplifts, $backed_out_bugs);

	$clean_backed_out_bugs = array_diff($backed_out_bugs, $uplifts);

	return [
		'uplifts' 	=> array_values($clean_uplifts),
		'backouts' 	=> array_values($clean_backed_out_bugs),
		'total' 	=> array_values(array_merge($clean_uplifts, $clean_backed_out_bugs))
	];
}


