<?php

namespace Deployer;

task('deploy:upload', function () {
    upload(getcwd() . '/deploy/build/packages/latest.zip', '{{ release_path }}');
    within(get('release_path'), function () {
        run('unzip -qq -o latest.zip');
        run('rm -f latest.zip');
        run('composer dumpautoload -o');
    });
})->desc('Uploading compiled project archive on server');
