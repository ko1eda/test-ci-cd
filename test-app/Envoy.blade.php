@servers(['web' => 'test-ci'])

@setup
    $repository = 'git@gitlab.com:koleda/test-ci-cd.git';
    $releases_dir = '/srv/releases';
    $app_dir = '/srv/test-ci';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy')
    clone_repository
    build_images
    run_composer
    update_permissions
    update_symlinks
    build_containers
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
@endtask

@task('build_images')
    echo 'Building container images'
    cd {{ $new_release_dir }}/build
    docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml build
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml run --rm php-fpm bash -c "composer install --prefer-dist --no-scripts -q -o"
    {{-- composer install --prefer-dist --no-scripts -q -o --}}
@endtask

@task('update_permissions')
    cd {{ $release_dir }};
    chgrp -R www-data {{ $release }}
    chmod -R ug+rwx {{ $release }}
@endtask

@task('update_symlinks')
    echo "Linking "
    {{-- rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current --}}

    ln -nfs {{ $new_release_dir }} {{ $app_dir }}
    chgrp -h www-data {{ $app_dir }}
@endtask

@task('build_containers')
    echo 'Building new containers'
    cd {{ $new_release_dir }}/build
    docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml down && \
    docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml up -d 
@endtask