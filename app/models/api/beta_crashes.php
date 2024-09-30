<?php

declare(strict_types=1);

use ReleaseInsights\{Beta, Json, URL, Utils};

$beta = new Beta();

$data = [];
foreach (range(1, $beta->count) as $beta_number) {
    $beta_number = (string) $beta->release . '.0b' . (string) $beta_number;
    $target = URL::Socorro->value . 'SuperSearch/?version=' . $beta_number . '&_facets=signature&product=Firefox';
    $temp = Json::load($target, 3600);
    if (empty($temp)) {
        $data[$beta_number] = [
            'total'      => 0,
            'signatures' => [],
        ];
        continue;
    }

    $data[$beta_number] = [
        'total'      => $temp['total'],
        'signatures' => $temp['facets']['signature'],
    ];
}

// Create a summary of the crashes across betas

$data['summary'] = [
   'total'   => array_sum(array_column($data, 'total')),
];


return $data;
