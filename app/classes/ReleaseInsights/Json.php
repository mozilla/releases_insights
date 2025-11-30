<?php

declare(strict_types=1);

namespace ReleaseInsights;

use Cache\Cache;

class Json
{
    /**
     * @param  array<string, string> $data
     */
    public function __construct(
        public array $data = [],
        public mixed $jsonp = false,
        public bool $pretty_print = false,
    )
    {
    }

    /**
     * Return a JSON/JSONP representation of data with the right HTTP headers
     */
    public function output(): string
    {
        $json = $this->pretty_print ? json_encode($this->data, JSON_PRETTY_PRINT) : json_encode($this->data);
        $mime = 'application/json';

        if (is_string($this->jsonp)) {
            $mime = 'application/javascript';
            $json = $this->jsonp . '(' . $json . ')';
        }

        ob_start();
        header("access-control-allow-origin: *"); // * is OK as our Json API is public and readonly
        header("Content-type: {$mime}; charset=UTF-8");
        header("Content-Length: " . strlen($json));
        echo $json;
        $json = ob_get_contents();
        ob_end_clean();

        return $json;
    }

    /**
     * Return HTTP code 400 and an error message if an API call is incorrect
     */
    public function outputError(int $code = 400): string
    {
        $this->pretty_print = true;
        http_response_code($code);
        return $this->output();
    }

    /**
     * Output Json data
     */
    public function render(): void
    {
        if (array_key_exists('error', $this->data)) {
            echo $this->outputError();
        } else {
            $this->jsonp = $_GET['callback'] ?? false;
            echo $this->output();
        }
    }

    // Below are static methods imported from the Utils class, refactoring in progress

    /**
     *  @return array<mixed> $template_data
     */
    public static function load(string $url, int $ttl = 0): array
    {
        if (! $data = Cache::getKey($url, $ttl)) {
            $data = Utils::getFile($url);

            // Error fetching external data, don't cache. Safety net
            // @codeCoverageIgnoreStart
            if ($data === false) {
                return ['error' => 'URL triggered an error'];
            }
            // @codeCoverageIgnoreEnd

            // No data returned, bug or incorrect data, don't cache.
            if (empty($data)) {
                return ['error' => 'URL provided no data'];
            }

            // Invalid Json, don't cache.
            if (! json_validate($data)) {
                return ['error' => 'Invalid JSON source'];
            }

            Cache::setKey($url, $data, $ttl);
        }

        return self::toArray($data);
    }

    /**
     * Return an Array from a Json string
     * This is an utility function as we use json_decode in multiple places,
     * always with the same options. That will make these calls shorter,
     * with a more explicit function name and will allow to change default
     * values at the app level.
     *
     * @return array<mixed>  Associative array from a Json string
     */
    public static function toArray(string $data): array
    {
        $data = json_decode(
            json: $data,
            associative: true,
            depth: 512,
        );

        return is_null($data) ? [] : $data;
    }
}

