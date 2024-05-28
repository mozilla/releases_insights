<?php

declare(strict_types=1);

namespace ReleaseInsights;

readonly class Model
{

    public function __construct(public string $model) {
    }

    /**
     * Return data from the model
     *
     * @return array<mixed>
     */
    public function get(): array
    {
        $data = require_once MODELS .
            match ($this->model) {
                'beta'     => 'beta.php', // @codeCoverageIgnore
                'owners'   => 'release_owners.php',
                default    => '404.php',
            };

        return $data;
    }
}