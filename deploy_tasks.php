<?php

namespace Deployer;

task('deploy:upload', function () {
    $stage = input()->hasArgument('stage') ? input()->getArgument('stage') : 'production';
    $packageInfo = getcwd() . '/.deploy/.build/packages/' . $stage;
    $package = file_exists($packageInfo) ? file_get_contents($packageInfo) : '';
    upload(getcwd() . '/.deploy/.build/packages/' . $package, '{{ release_path }}');
    within(get('release_path'), function () use ($package) {
        run('unzip -qq -o ' . $package);
        run('rm -f ' . $package);
        run('composer dumpautoload -o');
    });
})->desc('Uploading compiled project archive on server');
