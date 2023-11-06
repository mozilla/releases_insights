<?php

declare(strict_types=1);

namespace ReleaseInsights;

use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;

class Template
{
    public string|bool $template_caching;

    /**
     *  @param array<mixed> $data
     */
    public function __construct(public string $template, public array $data)
    {
        // Cache compiled templates on production
        $this->template_caching = PRODUCTION ? CACHE_PATH : false;

        // @codeCoverageIgnoreStart
        // Pass extra variables to template in local dev mode
        if (LOCALHOST && !defined('TESTING_CONTEXT')) {
            $this->data += [
                'branch' => trim((string) shell_exec('git rev-parse --abbrev-ref HEAD')),
            ];
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @codeCoverageIgnore
     */
    public function render(): void
    {
        // Initialize our Templating system
        $twig_loader = new FilesystemLoader(INSTALL_ROOT . 'app/views/templates');

        // @codeCoverageIgnoreStart
        // Allow Twig debug mode in local dev mode
        if (LOCALHOST && !defined('TESTING_CONTEXT')) {
            $twig = new Environment($twig_loader, ['debug' => true,]);
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        } else {
            $twig = new Environment($twig_loader);
        }
        // @codeCoverageIgnoreEnd

        $twig->addExtension(new IntlExtension());
        echo $twig->render($this->template, $this->data);
    }
}
