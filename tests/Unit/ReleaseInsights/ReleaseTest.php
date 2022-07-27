<?php

declare(strict_types=1);

use ReleaseInsights\Release;

test('Release->getSchedule()', function () {
    $obj = new Release('102.0');
    expect($obj->getSchedule())
        ->toBeArray();
    $obj = new Release('error');
    expect($obj->getSchedule())
        ->toBeArray();
});


test('Release->getNiceLabel()', function () {
    expect(Release::getNiceLabel('103', 'soft_code_freeze'))
        ->toEqual('103 soft Code Freeze');
    expect(Release::getNiceLabel('104', 'release'))
        ->toEqual('104 Release');
    expect(Release::getNiceLabel('104', 'release', false))
        ->toEqual('Firefox 104 go-live @ 6am PT');
});
