<?php
namespace ReleaseInsights;

class Utils
{
    /* Utility function to include a file and return the output as a string */
    public static function includeBuffering(string $file): string
    {
        ob_start();
        include $file;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
