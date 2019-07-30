<?php
require_once __DIR__ . '/../app/bootstrap.php';


$bugs = getBugsFromHgWeb($query);

// redirect to Bugzilla if &redirect is set in url
if ($redirect) {
	header('Location:' . bzBugList($bugs['uplifts']));
	exit;
}

if ($json) {
	print outputJson(
		bzBugList(
			[
				'desc' => 'Beta uplifts and backouts',
				'version' => $params['version'],
				'uplifts_count' => count($bugs['uplifts']),
				'backouts_count' => count($bugs['backouts']),
				'total_count' => count($bugs['total']),
				'uplifts' => $bugs['uplifts'],
				'backouts' => $bugs['backouts'],
				'total' => $bugs['total']
			],
			false
		)
	);
	exit;
}

print "<h3>Beta uplifts in Firefox ${params['version']}</h3>";
print 'Uplifts: <a href="'. bzBugList($bugs['uplifts']) . '">' . count(array_unique($bugs['uplifts'])) . '</a><br>';
print 'Backouts: <a href="'. bzBugList($bugs['backouts']) . '">' . count($bugs['backouts']) . '</a><br>';
print 'Total: <a href="'. bzBugList($bugs['total']) . '">' . count($bugs['total']) . '</a><br>';
exit;
