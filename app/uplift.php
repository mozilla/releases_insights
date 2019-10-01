<?php

function getBugsFromHgWeb($query) :array
{
    $results    = getJson($query)['pushes'];
    $changesets = array_column($results, 'changesets');
    $uplifts    = [];
    $backouts   = [];

    // extract bug number from commit message
    $get_bugs = function ($str) {
        if (preg_match_all("/bug \d+/", $str, $matches)) {
            return array_map('trim', str_replace('bug', '', $matches[0]));
        }
        return [];
    };

    foreach ($changesets as $items) {
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
                '[mozharness]', 'r=aki', 'r=tomprince', 'r=mtabara', 'a=jorgk',
                'beetmover', '[taskgraph]', 'a=testonly', 'a=bustage', 'a=expectation-update-for-worker-image'
            ])) {
                continue;
            }

            if (inString($subitem, ['backed out', 'back out'])) {
                $counter = count($uplifts);
                $uplifts = array_diff($uplifts, $get_bugs($subitem));
                if ($counter == count($uplifts)) {
                    $backouts[] = $subitem;
                }
                continue;
            }

            // We only include the first bug number mentionned for normal cases
            $uplifts = array_merge($uplifts, array_slice($get_bugs($subitem), 0, 1));
        }
    }

    $uplifts = array_unique($uplifts);

    $backed_out_bugs = [];

    foreach ($backouts as $backout) {
        $backed_out_bugs = array_merge($backed_out_bugs, $get_bugs($backout));
    }

    $backed_out_bugs = array_unique($backed_out_bugs);

    // Substract uplifts that were backed out later
    // $clean_uplifts = array_diff($uplifts, $backed_out_bugs);
    $clean_uplifts = $uplifts;
    $clean_backed_out_bugs = array_diff($backed_out_bugs, $uplifts);

    return [
        'uplifts'   => array_values($clean_uplifts),
        'backouts'  => array_values($clean_backed_out_bugs),
        'total'     => array_values(array_merge($clean_uplifts, $clean_backed_out_bugs))
    ];
}
