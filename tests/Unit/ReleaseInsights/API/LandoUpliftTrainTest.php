<?php
declare(strict_types=1);

use ReleaseInsights\API\LandoUpliftTrain;

test('LandoUpliftTrain->getTrains()', function () {
    $obj = new LandoUpliftTrain();
    // Root structure
    expect($obj->getTrains())
        ->toBeArray()
        ->toHaveCount(3) // We have 3 root keys only
        ->toHavekeys(['nightly', 'beta', 'release']); // Keys are immutable

    // Train general structure
    expect($obj->getTrains()['nightly'])
        ->toHaveCount(2)
        ->toHavekeys(['version', 'release_date']);
    expect($obj->getTrains()['beta'])
        ->toHaveCount(4)
        ->toHavekeys(['version', 'release_date', 'has_betas_left', 'is_rc_shipped']);
    expect($obj->getTrains()['release'])
        ->toHaveCount(2)
        ->toHavekeys(['version', 'release_date']);

    // Make sure versions are sequential
    expect($obj->getTrains()['nightly']['version'] - $obj->getTrains()['beta']['version'])
        ->toBe(1);
    expect($obj->getTrains()['beta']['version'] - $obj->getTrains()['release']['version'])
        ->toBe(1);

    // Check each individial type and format
    expect($obj->getTrains()['nightly']['version'])
        ->toBeInt();
    expect($obj->getTrains()['beta']['version'])
        ->toBeInt();
    expect($obj->getTrains()['release']['version'])
        ->toBeInt();
    expect($obj->getTrains()['nightly']['release_date'])
        ->toBeString()
        ->toMatch('/^\d{4}-\d{2}-\d{2}$/');;
    expect($obj->getTrains()['beta']['release_date'])
        ->toBeString()
        ->toMatch('/^\d{4}-\d{2}-\d{2}$/');;
    expect($obj->getTrains()['release']['release_date'])
        ->toBeString()
        ->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    expect($obj->getTrains()['beta']['has_betas_left'])
        ->toBeBool();
    expect($obj->getTrains()['beta']['is_rc_shipped'])
        ->toBeBool();
});