<?php

namespace Deployer;

task('resources:compile', function () {
    within(get('release_path'), function () {
        run('yarn run production');
    });
})->desc('Compiling project resources');;
