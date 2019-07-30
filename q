[1mdiff --git a/app/uplift.php b/app/uplift.php[m
[1mindex 3cdd4b6..e42e77e 100644[m
[1m--- a/app/uplift.php[m
[1m+++ b/app/uplift.php[m
[36m@@ -2,11 +2,19 @@[m
 [m
 function getBugsFromHgWeb($query) :array[m
 {[m
[32m+[m	[32merror_log($query);[m
 	$results    = getJson($query)['pushes'];[m
 	$changesets = array_column($results, 'changesets');[m
 	$uplifts    = [];[m
 	$backouts   = [];[m
 [m
[32m+[m	[32m$get_bugs = function($str) {[m
[32m+[m		[32mif (preg_match("/bug \d+/", $str, $matches)) {[m
[32m+[m			[32mreturn [trim(str_replace('bug', '', $matches[0]))];[m
[32m+[m		[32m}[m
[32m+[m		[32mreturn [];[m
[32m+[m	[32m};[m
[32m+[m
 	foreach($changesets as $items) {[m
 		foreach ($items as $subitem) {[m
 			$subitem = explode("\n", $subitem['desc'])[0];[m
[36m@@ -27,32 +35,27 @@[m [mfunction getBugsFromHgWeb($query) :array[m
 [m
 			if (startsWith($subitem, 'backed out')) {[m
 				$backouts[] = $subitem;[m
[32m+[m				[32m$uplifts = array_diff($uplifts, $get_bugs($subitem));[m
 				continue;[m
 			}[m
 [m
[31m-			if (preg_match("/bug \d+/", $subitem, $matches)) {[m
[31m-				$uplifts[] = trim(str_replace('bug', '', $matches[0]));[m
[31m-			}[m
[32m+[m			[32m$uplifts = array_merge($uplifts, $get_bugs($subitem));[m
 		}[m
 	}[m
 [m
 	$uplifts = array_unique($uplifts);[m
 [m
[31m-[m
 	$backed_out_bugs = [];[m
[32m+[m
 	foreach($backouts as $backout) {[m
[31m-		if (preg_match_all("/bug \d+/", $backout, $matches) !== false) {[m
[31m-			$matches = str_replace('bug ', '', $matches[0]);[m
[31m-			$backed_out_bugs = array_merge($backed_out_bugs, $matches);[m
[31m-		}[m
[32m+[m		[32m$backed_out_bugs = array_merge($backed_out_bugs, $get_bugs($backout));[m
 	}[m
 [m
[31m-	$backed_out_bugs= array_unique($backed_out_bugs);[m
[31m-[m
[32m+[m	[32m$backed_out_bugs = array_unique($backed_out_bugs);[m
 [m
 	// Substract uplifts that were backed out later[m
[31m-	$clean_uplifts = array_diff($uplifts, $backed_out_bugs);[m
[31m-[m
[32m+[m	[32m// $clean_uplifts = array_diff($uplifts, $backed_out_bugs);[m
[32m+[m	[32m$clean_uplifts = $uplifts;[m
 	$clean_backed_out_bugs = array_diff($backed_out_bugs, $uplifts);[m
 [m
 	return [[m
