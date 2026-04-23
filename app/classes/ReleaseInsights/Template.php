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
        // Cache compiled templates on production in a twig folder (10x difference)
        $this->template_caching = IS_DEV_MODE ? false : CACHE_PATH . 'twig/' ;

        // @codeCoverageIgnoreStart
        // Pass extra variables to template in local dev mode
        if (IS_DEV_MODE && !defined('TESTING_CONTEXT')) {
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
        if (IS_DEV_MODE && !defined('TESTING_CONTEXT')) {
            $twig = new Environment(
                $twig_loader,
                [
                    'cache' => $this->template_caching,
                    'debug' => true,
                    'auto_reload' => true,
                ]
            );
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        } else {
            $twig = new Environment($twig_loader, ['cache' => $this->template_caching,]);
        }
        // @codeCoverageIgnoreEnd

        $twig->addExtension(new IntlExtension());
        echo $twig->render($this->template, $this->data);
        die;
    }
}
