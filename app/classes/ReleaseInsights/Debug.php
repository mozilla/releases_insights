<?php

declare(strict_types=1);

namespace ReleaseInsights;

class Debug
{
    /**
     * Utility function to get symfony dump() function output to the CLI
     * http://symfony.com/doc/current/components/var_dumper/
     *
     * @codeCoverageIgnore
     */
    public static function dump(): void
    {
        if (! class_exists(\Symfony\Component\VarDumper\Dumper\CliDumper::class)) {
            return;
        }

        $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
        $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper();
        foreach (func_get_args() as $arg) {
            $dumper->dump($cloner->cloneVar($arg));
        }
    }
}
