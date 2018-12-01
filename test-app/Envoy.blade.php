@servers(['web' => 'test-ci'])

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
    build_images
    install_dependencies
    move_env
    update_symlinks
    build_containers
    update_permissions
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
@endtask

@task('build_images')
    echo 'Building container images'
    cd {{ $new_release_dir }}/build
    export APP_MOUNT={{ $release_mount }}
    docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml build
@endtask

@task('install_dependencies')
    echo "Starting deployment ({{ $release }})" 
    cd {{ $new_release_dir }}
    export APP_MOUNT={{ $release_mount }}
    echo "Running composer install" 
    docker-compose -f build/docker-compose.base.yml -f build/docker-compose.prod.yml run --rm --user 1002 php-fpm bash -c "composer install --prefer-dist --no-scripts -q -o"
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
    docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml down && \
    docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml up -d 
@endtask

@task('update_permissions')
    echo "Updating app directory permissions" 
    cd {{ $app_dir }}/{{ $app_name }}
    sudo chown -R deployer:www-data storage/ 
    sudo chmod -R 2770 storage/

    echo "Updating build directory permissions" 
    {{-- Then restrict permission on the build files --}}
    cd {{ $app_dir }}
    sudo chown -R root:ec2-user build
    sudo chmod -R 770 build
@endtask
