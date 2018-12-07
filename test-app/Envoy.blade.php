@servers(['web' => "deployer@{$IP}"])

@setup
    $repository = 'git@gitlab.com:koleda/test-ci-cd.git';
    $releases_dir = '/srv/releases';
    $app_dir = '/srv/app';
    $app_name= 'test-app';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
    $release_mount = $releases_dir . '/' . $release . '/' . $app_name;
@endsetup

@story('deploy')
    clone_repository
    login_to_gitlab_registry
    install_composer_dependencies
    install_npm_dependencies
    move_env
    update_symlinks
    build_containers
    run_migrations
    update_permissions
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
@endtask

@task('login_to_gitlab_registry')
    {{-- login to gitlab with the passed in credential so that we can pull the images from our private repo--}}
    docker login -u koleda -p {{ $GITLAB_SECRET }} registry.gitlab.com;
@endtask

@task('install_composer_dependencies')
    echo "Starting deployment ({{ $release }})" ;
    cd {{ $new_release_dir }};
    export APP_MOUNT={{ $release_mount }};
    echo "Running composer install";
    docker-compose -f build/docker-compose.prod.yml run --rm --user 1002 php-fpm composer install --prefer-dist --no-scripts -q -o;

    {{-- echo "Optamizing composer installs";
    docker-compose -f build/docker-compose.base.yml -f build/docker-compose.prod.yml run --rm --user 1002 php-fpm php artisan clear-compiled --env=production && php artisan optimize --env=production --}}
@endtask

@task('install_npm_dependencies')
    echo "Running npm install and building assets"
    export APP_MOUNT={{ $release_mount }}
    docker-compose -f build/docker-compose.prod.yml run --rm -w /var/www/html node bash -c "npm install && npm run production"
@endtask

@task('move_env')
    echo "Moving .env file from build to app"
    cp {{ $new_release_dir }}/build/php/.env {{ $new_release_dir }}/{{ $app_name}}/.env 
@endtask

@task('update_symlinks')
    echo "Linking {{ $new_release_dir }} -> {{ $app_dir }}" 
    {{-- rm -rf {{ $new_release_dir }}/storage  
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current --}}

    ln -nfs {{ $new_release_dir }} {{ $app_dir }}
    chgrp -h www-data {{ $app_dir }}
@endtask

{{-- Build containers and mount the newly linked app directory to them  --}}
@task('build_containers')
    echo 'Building new containers'
    cd {{ $new_release_dir }}/build

    export APP_MOUNT={{ $app_dir}}/{{ $app_name}}/
    docker-compose -f docker-compose.prod.yml down && \
    docker-compose -f docker-compose.prod.yml up -d 
@endtask

@task('run_migrations')
    echo "Running php artisan migrate"
    cd {{ $new_release_dir }}/build
    docker-compose -f docker-compose.prod.yml run --rm php-fpm php artisan migrate
@endtask

@task('update_permissions')
    echo "Updating app directory permissions" 
    cd {{ $app_dir }}/{{ $app_name }}
    sudo chown -R deployer:www-data storage/ 
    sudo chmod -R 2770 storage/
  
    echo "Updating build directory permissions" 
    {{-- Then restrict permission on the build files --}}
    cd {{ $app_dir }}
    sudo chown -R root:ec2-user build ci
    sudo chmod -R 770 build ci
@endtask

{{-- Not part of the story but useful for testing  --}} 
@task('remove_old_builds')
  docker rm -f $(docker ps -aq)
  docker volume rm $(docker volume ls -q)
  yes | sudo rm -r {{ $releases_dir }}
  rm {{ $app_dir }}
@endtask