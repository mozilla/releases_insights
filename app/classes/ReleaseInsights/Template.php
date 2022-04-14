<?php
declare(strict_types=1);

namespace ReleaseInsights;

use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Dotenv\Dotenv;

class Template
{
    /**
     *  @var array<mixed> $data
     */
    public array $data;
    public string $template;
    public string|bool $template_caching;

    /**
     *  @param array<mixed> $template_data
     */
    public function __construct(string $template_file, array $template_data)
    {
        $this->data = $template_data;
        $this->template = $template_file;

        // Cache compiled templates on production
        $dotenv = Dotenv::createImmutable(INSTALL_ROOT);
        $dotenv->safeLoad();
        $this->template_caching = isset($_ENV['TWIG_CACHING']) && $_ENV['TWIG_CACHING'] == 'no' ? false : CACHE_PATH;
    }

    /**
     * @codeCoverageIgnore
     */
    public function render(): void
    {
        // Initialize our Templating system
        $twig_loader = new FilesystemLoader(INSTALL_ROOT . 'app/views/templates');
        $twig = new Environment($twig_loader, ['cache' => $this->template_caching]);
        $twig->addExtension(new IntlExtension());

        print $twig->render($this->template, $this->data);
    }
}
