<?php

namespace Deployer;

// project custom storage prepare
use RuntimeException;

task('git:submodules:install', function () {
    within(get('release_path'), function () {
        run('./.utils/git/submodules/update.sh');
    });
})->desc('Installing project submodules');

task('storage:prepare', function () {
    run('{{bin/php}} {{release_path}}/artisan storage:prepare');
})->desc('Creating project custom storage directories');

task('symlinks:prepare', function () {
    run('{{bin/php}} {{release_path}}/artisan symlinks:prepare --production');
})->desc('Creating project symlinks');

task('cron:install', function () {
    run('job="* * * * * {{bin/php}} {{deploy_path}}/current/artisan schedule:run >> /dev/null 2>&1";'
        . 'ct=$(crontab -l |grep -i -v "$job");(echo "$ct" ;echo "$job") |crontab -');
})->desc('Adding the laravel cron to the user crontab');

task('server:resources:reload', function () {
    run('sudo service nginx reload');
    run('if [ -f "/etc/init.d/php7.0-fpm" ]; then sudo service php7.0-fpm restart; fi');
    run('if [ -f "/etc/init.d/php7.1-fpm" ]; then sudo service php7.1-fpm restart; fi');
    run('if [ -f "/etc/init.d/php7.2-fpm" ]; then sudo service php7.2-fpm restart; fi');
})->desc('Reloading the server resources');

task('project:dependencies_check', function () {
    within(get('release_path'), function () {
        $result = run('./.utils/server/configCheck.sh');
        if (strpos(strtolower($result), 'are missing from your server') !== false) {
            throw new RuntimeException("Project dependencies are missing from the server");
        }
    });
})->desc('Checking server dependencies');

task('supervisor:restart', function () {
    within(get('release_path'), function () {
        $result = run('./.utils/supervisor/restart.sh');
        if (strpos(strtolower($result), 'the supervisor project config does not exist') !== false) {
            throw new RuntimeException("The supervisor project config does not exist");
        }
    });
})->desc('Restarting the project supervisor daemon');
