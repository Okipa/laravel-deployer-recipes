<?php

namespace Deployer;

task('build:create_package', function () {
    within(get('release_path'), function () {
        run("mkdir -p {{ deploy_path }}/packages");
        if (file_exists(get('deploy_path') . '/packages/latest.zip')) {
            run("rm -f {{ deploy_path }}/packages/latest.zip");
        }
        run("zip -qr --exclude=\"*.git*\" --exclude=\"*node_modules*\" {{ deploy_path }}/packages/latest.zip * .[!.]*");
    });
})->desc('Creating compiled project archive');
