<?php
declare(strict_types=1);

namespace ReleaseInsights;

use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;

class Template
{
    /**
     *  @var array<mixed> $data
     */
    public array $data;
    public string $template;

    /**
     *  @param array<mixed> $template_data
     */
    public function __construct(string $template_file, array $template_data)
    {
        $this->data = $template_data;
        $this->template = $template_file;
    }


    /**
     * @codeCoverageIgnore
     */
    public function render(): void
    {
        // Initialize our Templating system
        $twig_loader = new FilesystemLoader(INSTALL_ROOT . 'app/views/templates');
        $twig = new Environment($twig_loader, ['cache' => false]);
        $twig->addExtension(new IntlExtension());

        print $twig->render($this->template, $this->data);
    }
}
