<?php

function mtrim($string)
{
    $string = explode(' ', $string);
    $string = array_filter($string);
    $string = implode(' ', $string);

    return $string;
}

/**
 * Check if $haystack starts with a string in $needles.
 * $needles can be a string or an array of strings.
 *
 * @param  string  $haystack String to analyse
 * @param  array   $needles  The string to look for
 * @return boolean True if the $haystack string starts with a string in $needles
 */
function startsWith($haystack, $needles)
{
    foreach ((array) $needles as $prefix) {
        if (! strncmp($haystack, $prefix, mb_strlen($prefix))) {
            return true;
        }
    }

    return false;
}

function getJson($url, $time = 1080)
{

    $cache_id = CACHE_PATH . sha1($url) . '.cache';

    // Serve from cache if it is younger than $cache_time
    $cache_ok = file_exists($cache_id) && time() - $time < filemtime($cache_id);

    if (! $cache_ok) {
        file_put_contents($cache_id, file_get_contents($url, true));
    }

    return json_decode(file_get_contents($cache_id), true);
}


function bzBugList($arr, $str_output=true)
{
    if ($str_output) {
        return 'https://bugzilla.mozilla.org/buglist.cgi?bug_id=' . implode('%2C',$arr);
    }
    return $arr;
}

function var_error_log( $object=null ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log( $contents );        // log contents of the result of var_dump( $object )
}

/**
 * Sanitize a string or an array of strings for security before template use.
 *
 * @param  string $string The string we want to sanitize
 * @return string Sanitized string for security
 */
function secureText($string)
{
    $sanitize = function ($v) {
        // CRLF XSS
        $v = str_replace(['%0D', '%0A'], '', $v);
        // We want to convert line breaks into spaces
        $v = str_replace("\n", ' ', $v);
        // Escape HTML tags and remove ASCII characters below 32
        $v = filter_var(
            $v,
            FILTER_SANITIZE_SPECIAL_CHARS,
            FILTER_FLAG_STRIP_LOW
        );

        return $v;
    };

    return is_array($string) ? array_map($sanitize, $string) : $sanitize($string);
}

/**
 * Return a JSON/JSONP representation of data
 *
 * @param  array   Array of data to encode in JSON format
 * @param  mixed   Can be a string (JSONP function name), or boolean.
 *                 Default value is false
 * @param  boolean If the output needs to be prettified.
 *                 Default value is false
 *
 * @return json JSON content
 */
function outputJson(array $data, $jsonp = false, $pretty_print = false)
{
    $json = $pretty_print ? json_encode($data, JSON_PRETTY_PRINT) : json_encode($data);
    $mime = 'application/json';

    if ($jsonp) {
        $mime = 'application/javascript';
        $json = $jsonp . '(' . $json . ')';
    }

    ob_start();
    header("access-control-allow-origin: *");
    header("Content-type: {$mime}; charset=UTF-8");
    header("Content-Length: " . strlen($json));
    echo $json;
    $json = ob_get_contents();
    ob_end_clean();

    return $json;
}


/**
 * Check if $needles are in $haystack
 *
 * @param string  $haystack  String to analyze
 * @param mixed   $needles   The string (or array of strings) to look for
 * @param boolean $match_all True if we need to match all $needles, false
 *                           if it's enough to match one. Default: false
 *
 * @return boolean True if the $haystack string contains any/all $needles
 */
function inString($haystack, $needles, $match_all = false)
{
    $matches = 0;
    foreach ((array) $needles as $needle) {
        if (mb_strpos($haystack, $needle, $offset = 0, 'UTF-8') !== false) {
            // If I need to match any needle, I can stop at the first match
            if (! $match_all) {
                return true;
            }
            $matches++;
        }
    }

    if (! $match_all) {
        return false;
    }

    return $matches == count($needles);
}
