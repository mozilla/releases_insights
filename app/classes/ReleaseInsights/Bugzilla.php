<?php
namespace ReleaseInsights;

class Bugzilla
{
    public static function getBugListLink(array $bug_numbers) : string
    {
        return 'https://bugzilla.mozilla.org/buglist.cgi?bug_id=' . implode('%2C', $bug_numbers);
    }

    public static function getBugsFromHgWeb(string $query, bool $detect_backouts = false) : array
    {
        $results = Utils::getJson($query)['pushes'];
        $changesets = array_column($results, 'changesets');
        $bug_fixes = [];
        $backouts = [];

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
                $subitem = strtolower(Utils::mtrim($subitem));

                if (Utils::startsWith($subitem, ['no bug', 'automatic version bump'])) {
                    continue;
                }

                // Commits can be ignored if they contain one of these strings
                if (Utils::inString($subitem, [
                    'a=test-only', 'a=release', 'a=npotb', 'a=searchfox-only',
                    'try-staging', 'taskcluster', 'a=tomprince', 'a=aki', 'a=testing',
                    '[mozharness]', 'r=aki', 'r=tomprince', 'r=mtabara', 'a=jorgk',
                    'beetmover', '[taskgraph]', 'a=testonly', 'a=bustage', 'a=expectation-update-for-worker-image',
                    'a=repo-update'
                ])) {
                    continue;
                }

                if (Utils::inString($subitem, ['backed out', 'back out']) && $detect_backouts === true) {
                    $counter = count($bug_fixes);
                    $bug_fixes = array_diff($bug_fixes, $get_bugs($subitem));
                    if ($counter == count($bug_fixes)) {
                        $backouts[] = $subitem;
                    }
                    continue;
                }

                // We only include the first bug number mentionned for normal cases
                $bug_fixes = array_merge($bug_fixes, array_slice($get_bugs($subitem), 0, 1));
            }
        }

        $bug_fixes = array_unique($bug_fixes);

        $backed_out_bugs = [];

        foreach ($backouts as $backout) {
            $backed_out_bugs = array_merge($backed_out_bugs, $get_bugs($backout));
        }

        $backed_out_bugs = array_unique($backed_out_bugs);

        // Substract bug_fixes that were backed out later
        $clean_bug_fixes = $bug_fixes;
        $clean_backed_out_bugs = array_diff($backed_out_bugs, $bug_fixes);

        return [
            'bug_fixes'   => array_values($clean_bug_fixes),
            'backouts'    => array_values($clean_backed_out_bugs),
            'total'       => array_values(array_merge($clean_bug_fixes, $clean_backed_out_bugs)),
        ];
    }
}
