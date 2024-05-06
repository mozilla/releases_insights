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
        header("access-control-allow-origin: *");
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
    public function outputError(): string
    {
        $this->pretty_print = true;
        http_response_code(400);

        return $this->output();
    }

    /**
     * Output Json data
     */
    public function render(): void
    {
        if (array_key_exists('error', $this->data)) {
            print_r($this->outputError());
        } else {
            $this->jsonp = $_GET['callback'] ?? false;
            print_r($this->output());
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

            // No data returned, bug or incorrect date, don't cache.
            if (empty($data)) {
                return [];
            }

            // Invalid Json, don't cache.
            if (! self::isValid($data)) {
                return [];
            }

            Cache::setKey($url, $data, $ttl);
        }

        return self::toArray($data);
    }

    public static function isValid(string $data): bool
    {
        return is_string($data)
            && is_array(json_decode($data, true))
            && (json_last_error() == JSON_ERROR_NONE);
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

