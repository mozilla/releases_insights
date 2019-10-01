<?php
namespace ReleaseInsights;

class Utils
{
    public static function includeBuffering(string $file): string
    {
        ob_start();
        // display the page
        include $file;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
