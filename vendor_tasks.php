<?php

namespace Deployer;

task('vendor:composer_setup', function () {
    within(get('release_path'), function () {
        $cachePath = get('deploy_path') . '/cache/vendor';
        $installPath = './vendor';
        if (is_dir($cachePath)) {
            set('composer_action', 'update');
        } else {
            run("mkdir -p {$cachePath}");
            set('composer_action', 'install');
        }
        run("ln -nfs {$cachePath} {$installPath}");
        set('composer_options', '{{ composer_action }} {{ composer_flags }}');
    });
})->desc('Setting up composer');

task('vendor:yarn_install', function () {
    within(get('release_path'), function () {
        $cachePath = get('deploy_path') . '/cache/node_modules';
        $installPath = './node_modules';
        if (is_dir($cachePath)) {
            run("ln -nfs {$cachePath} {$installPath}");
        } else {
            run("mkdir -p {$cachePath}");
            run("ln -nfs {$cachePath} {$installPath}");
        }
        run('yarn install');
    });
})->desc('Installing project node dependencies (with cache)');

task('vendor:yarn_install_without_cache', function () {
    within(get('release_path'), function () {
        run('yarn install');
    });
})->desc('Installing project node dependencies (without cache)');

task('vendor:bower_install', function () {
    within(get('release_path'), function () {
        if (is_dir(get('deploy_path') . '/cache/bower_components')) {
            run('ln -nfs {{ deploy_path }}/cache/bower_components ./bower_components');
            run('yarn bower update --quiet --config.interactive=false');
        } else {
            run('mkdir -p {{ deploy_path }}/cache/bower_components');
            run('ln -nfs {{ deploy_path }}/cache/bower_components ./bower_components');
            run('yarn bower install --quiet --config.interactive=false');
        }
    });
})->desc('Installing project bower dependencies');
