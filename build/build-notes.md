# Build Notes

## Steps to setup CD
1. Gitlab. Follow this tutorial https://docs.gitlab.com/ee/ci/examples/laravel_with_gitlab_and_envoy/
2. Gitlab CI settings for laravel https://laracasts.com/discuss/channels/testing/laravel-ci-testing-with-gitlab
3. Setting up envoyer (serversforhackers) https://serversforhackers.com/c/deploying-with-envoy-cast 
  + note envoyer doesn't work on windows so use docker as proxy -- see docker file)
4. You will need a new user on your server that has ssh login access, this user will be the deployer runner
  + Setting up a new user in aws (this will be the deployer user and we will create an ssh key for that user to log into the server with) https://aws.amazon.com/premiumsupport/knowledge-center/new-user-accounts-linux-instance/
  + Note that you need to run deployer as the deployer user you created so that user will need to be able to ssh into the server (see tutorial above)
4. You will need an sssh config file for envoyer to know how to access the server's -- Configuring /.ssh/config file https://nerderati.com/2011/03/17/simplify-your-life-with-an-ssh-config-file/
5. The new user may need sudo access to run certain commands. Enable sudo access for new user https://www.helicaltech.com/create-multiple-sudo-users-to-ec2-amazon-linux/
```
sudo su
visudo 
newuser ALL=(ALL)NOPASSWD:ALL (enter this on the last line of the file, save and quit)
```
6. Do not generate new laravel keys for each application instance you have, the application key is used to encrpyt/decrypt sensative information from the app and must be the same
  + https://laracasts.com/discuss/channels/general-discussion/app-key-for-laravel-app-in-a-different-server
7. Note that you will not need to have composer install specific dependencies like predis, they will be added to your composer.json file as you develop the application. The single composer install statement will install all your composer dependencies 
```
docker-compose -f build/docker-compose.base.yml -f build/docker-compose.prod.yml run --rm --user 1002 php-fpm bash -c "composer install --prefer-dist --no-scripts -q -o"
```
8. Possible steps to improve envoy script https://bosnadev.com/2015/01/07/brief-introduction-laravel-envoy/
  + note that you can use functions in bash scripts https://ryanstutorials.net/bash-scripting-tutorial/bash-functions.php

## Steps to setup CI
9. Article talks about setting up gitlab runner, setting up a private gitlab server(didn't actually do this part), etc. 
  + https://www.digitalocean.com/community/tutorials/how-to-set-up-continuous-integration-pipelines-with-gitlab-ci-on-ubuntu-16-04 
10. Talks about setting up docker container registries on gitlab (with example)
  + again follow this tutoruial https://docs.gitlab.com/ee/ci/examples/laravel_with_gitlab_and_envoy/
  + https://www.digitalocean.com/community/tutorials/how-to-build-docker-images-and-host-a-docker-image-repository-with-gitlab
```
# First cd into the directory that contains the dockerfile you want to use for the image
cd /build/php 
docker build -t registry.gitlab.com/koleda/test-ci-cd/koledachris/php-fpm:2.0.0 .
docker push registry.gitlab.com/koleda/test-ci-cd/koledachris/php-fpm:2.0.0 
```
11. Setting up testing with PHP https://docs.gitlab.com/ee/ci/examples/php.html#test-php-projects-using-the-docker-executor

### Other useful links that helped during set up 
+ __Running commands in a docker container that does not have them installed (ex netstat in an alpine container__
  + https://stackoverflow.com/questions/40350456/docker-any-way-to-list-open-sockets-inside-a-running-docker-container
+ How ports work in general and in docker 
  + https://stackoverflow.com/questions/3329641/how-do-multiple-clients-connect-simultaneously-to-one-port-say-80-on-a-server

## Steps to setup the Server 
### To Build Project
1. Build all images before running docker-compose up
2. Create a tempoarary php container to install a new laravel app using: (note: $(pwd) -- this represents the root directory of the project itself not the application)
```
run --rm -it -v $(pwd):/opt koledachris/php-fpm:1.0.0 bash -c "cd /opt && composer create project --prefer-dist laravel/laravel test-app" 
```
3. Move .env file from php folder into test-app directory
```
sudo cp srv/domain_name/build/.env  /srv/domain_name/test-app/.env
``` 
4. Generate a new application key (run your php container and use it run)
```
docker run --rm -it -v $(pwd):/opt -w /opt koledachris/php-fpm:1.0.0 php artisan key:generate
```
5. Run any migrations if you have them
```
docker run --rm -it -v $(pwd):/opt -w /opt koledachris/php-fpm:1.0.0 php artisan migrate
```
### To setup production server
1. Run startup.sh
  + How to check if a group exists in bash script 
    + https://stackoverflow.com/questions/29073210/how-to-check-if-a-group-exists-and-add-if-it-doesnt-in-linux-shell-script
    + https://unix.stackexchange.com/questions/191934/how-to-check-the-string-is-null-or-not-in-shell-script
  + Execute commands as different user in script https://unix.stackexchange.com/questions/264237/how-can-i-execute-a-script-as-root-execute-some-commands-in-it-as-a-specific-us
  +  Check for file non-existence https://stackoverflow.com/questions/638975/how-do-i-tell-if-a-regular-file-does-not-exist-in-bash
2. 

## Environment Variables and secrets management 
+ Passing environment variables to SQL scripts https://stackoverflow.com/questions/76065/how-do-i-pass-a-variable-to-a-mysql-script
+ Secrets management with vault (high level overview) https://www.youtube.com/watch?v=VYfl-DpZ5wM
+ Using Hashicorp vault with aws would not be possilbe with current budget due to number of servers required to host a vault cluster, 
  + https://aws.amazon.com/quickstart/architecture/vault/
  + https://testdriven.io/managing-secrets-with-vault-and-consul
+ Alternative for small projects on aws will be 
  + AWS SECRETS MANAGER - https://aws.amazon.com/secrets-manager/pricing 
  + Possibly AWS Parameter store (not sure what it is really) https://www.reddit.com/r/devops/comments/8fvphs/what_is_your_favorite_secret_management_solution/
+ Kubernetes can also be used to store secrets (some info for future use when learning kubernetes)
  + Minikube can be used to control a kubernetes cluster on your local machine https://kubernetes.io/docs/setup/minikube/
  + https://www.reddit.com/r/docker/comments/7mo09s/what_is_the_best_way_to_pass_passwords_in_the/