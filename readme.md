# Laravel Deployer recipes
Extra [Deployer](https://deployer.org) recipes for Laravel projects.

## Installation
- Install the extra laravel recipes in your project with the following command : `git submodule add https://github.com/Okipa/laravel-deployer-recipes.git .deploy`.
- Make sure you add the following lines in the `scripts` part of your `composer.json` file in order to make sure you always have an updated version of this git submodule :
```
"post-install-cmd": [
    ...
    "git submodule sync --recursive && git submodule update --init --recursive",
    ...
],
"post-update-cmd": [
    ...
    "git submodule sync --recursive && git submodule update --recursive --remote",
    ...
]
```
- Add the `deployer.phar` to your project root : https://deployer.org/docs/getting-started
- Create a `deploy.php` file at the root of your project with the following content (adjusted according your project needs) :
```php
namespace Deployer;

require 'recipe/laravel.php';
require './.deploy/asset_tasks.php';
require './.deploy/build_tasks.php';
require './.deploy/deploy_tasks.php';
require './.deploy/project_tasks.php';
require './.deploy/vendor_tasks.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// define servers
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$servers = [
    'preprod'    => [
        'host'             => '[domain_or_ip]',
        'branch'           => 'develop',
        'deploy_path'      => '[path_to_preprod_releases]',
        'user'             => '[preprod_user]',
        'http_user'        => '[preprod_http_user]',
        'http_group'       => '[http_group]',
        'private_identity' => '~/.ssh/id_rsa',
    ],
    'production' => [
        'host'             => '[domain_or_ip]',
        'branch'           => 'master',
        'deploy_path'      => '[path_to_production_releases]',
        'user'             => '[production_user]',
        'http_user'        => '[production_http_user]',
        'http_group'       => '[http_group]',
        'private_identity' => '~/.ssh/id_rsa',
    ],
];

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// servers configuration
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// we set the required configurations
set('repository', 'git@gitlab.acid.fr:acid-solutions/nsn-app.git');
set('composer_flags', '--no-dev --verbose --prefer-dist --optimize-autoloader --no-progress --no-interaction');
// we override the default deployer configurations
set('keep_releases', 3);
set('default_stage', 'preprod');
set('allow_anonymous_stats', false);
set('writable_mode', 'chmod');
set('writable_use_sudo', false);
set('bin/composer', function(){
    return run('which composer');
});
// we add custom configurations to laravel recipe
add('shared_dirs', ['public/files']);
// we configure servers
foreach ($servers as $stage => $server) {
    host($stage)
        ->stage($stage)
        ->hostname($server['host'])
        ->user($server['user'])
        ->identityFile($server['private_identity'])
        ->set('branch', $server['branch'])
        ->set('deploy_path', $server['deploy_path'])
        ->set('http_user', $server['http_user'])
        ->set('http_group', $server['http_group']);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// build and deployment tasks
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
task('build', function() use ($servers) {
    set('deploy_path', getcwd() . '/.deploy/.build');
    set('branch', $servers[input()->getArgument('stage')]['branch']);
    invoke('deploy:prepare');
    invoke('deploy:release');
    invoke('deploy:update_code');
    invoke('deploy:writable');
    invoke('vendor:composer_setup');
    invoke('git:submodules:install');
    invoke('deploy:vendors');
    invoke('vendor:yarn_install');
    invoke('vendor:bower_install');
    invoke('resources:compile');
    invoke('deploy:symlink');
    invoke('build:create_package');
    invoke('cleanup');
})->local()->desc('Locally building and compiling project archive');

task('release', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:upload',
    'git:submodules:install',
    'project:dependencies_check',
    'deploy:shared',
    'deploy:writable',
    'artisan:storage:link',
    'artisan:view:clear',
    'artisan:cache:clear',
    'artisan:config:cache',
    // 'artisan:route:cache', // only uncomment if the app is NOT multilingual
    'artisan:optimize',
    'artisan:migrate',
    'artisan:queue:restart',
    'supervisor:restart',
    'storage:prepare',
    'symlinks:prepare',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'server:resources:reload',
    'cron:install',
])->desc('Releasing compiled project archive on server');

task('deploy', [
    'build',
    'release',
])->desc('Starting project deployment process');

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// custom tasks chaining
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
after('deploy:failed', 'deploy:unlock');
```

## Available recipes

### asset_tasks.php
- `resources:compile` : Compiling project resources.

### build_tasks.php
- `build:create_package` : Creating compiled project archive.

### deploy_tasks.php
- `deploy:upload` : Uploading compiled project archive on server.

### project_tasks.php
- `git:submodules:install` : Installing project submodules.
- `storage:prepare` : Creating project custom storage directories.
- `symlinks:prepare` : Creating project symlinks.
- `cron:install` : Adding the laravel cron to the user crontab.
- `server:resources:reload` : Reloading the server resources.
- `project:dependencies_check` : Project dependencies are missing from the server.
- `supervisor:restart` : Restarting the project supervisor daemon.

### vendor_tasks.php
- `vendor:composer_setup` : Setting up composer.
- `vendor:yarn_install` : Installing project node dependencies.
- `vendor:bower_install` : Installing project bower dependencies.
