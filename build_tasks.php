<?php

namespace Deployer;

task('build:check_latest', function() {
    if (! file_exists(get('deploy_path') . '/current')) {
        throw new \RuntimeException("No prepared package was found.");
    }
    within(get('deploy_path') . '/current', function() {
        run("git fetch origin");
        $gitSha = run("git rev-parse origin/{{ branch }}");
        $stage = input()->hasArgument('stage') ? input()->getArgument('stage') : 'production';
        $packageName = $gitSha . '_' . $stage . '.zip';
        $checkFile = get('deploy_path') . '/packages/' . $packageName;
        if (! file_exists($checkFile)) {
            throw new \RuntimeException("The prepared package is not up to date.");
        }
        file_put_contents(getcwd() . '/.deploy/.build/packages/' . $stage, $packageName);
    });
})->desc('Check the latest package. Throws a RuntimeException if the latest zip is not the last commit.');

task('build:create_package', function() {
    within(get('release_path'), function() {
        run("mkdir -p {{ deploy_path }}/packages");
        if (file_exists(get('deploy_path') . '/packages/latest.zip')) {
            run("rm -f {{ deploy_path }}/packages/latest.zip");
        }
        run("zip -qr --exclude=\"*.git*\" --exclude=\"*node_modules*\" {{ deploy_path }}/packages/latest.zip * .[!.]*");
    });
})->desc('Creating compiled project archive');
