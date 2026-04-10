<?php

declare(strict_types=1);

use ReleaseInsights\{Model, Template};

[$graph_data] = new Model('beta_uplifts_graph')->get();

new Template(
    'beta_uplifts_graph.html.twig',
    [
        'page_title'  => 'Beta uplifts per release',
        'css_page_id' => 'beta_uplifts_graph',
        'graph_data'   => $graph_data,
        'graph_labels' => array_keys($graph_data),
        'graph_values' => array_values($graph_data),
    ]
)->render();
