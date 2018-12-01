# Build Notes

## Steps to setup CI/CD Pipeline
1. Gitlab. Follow this tutorial https://docs.gitlab.com/ee/ci/examples/laravel_with_gitlab_and_envoy/
2. Gitlab CI settings for laravel https://laracasts.com/discuss/channels/testing/laravel-ci-testing-with-gitlab
3. Setting up envoyer (serversforhackers) https://serversforhackers.com/c/deploying-with-envoy-cast (note envoyer doesn't work on windows so use docker as proxy)
  + Setting up a new user in aws (this will be the deployer user and we will create an ssh key for that user to log into the server with) https://aws.amazon.com/premiumsupport/knowledge-center/new-user-accounts-linux-instance/
  + Note that you need to run deployer as the deployer user you created so that user will need to be able to ssh into the server (see tutorial above)
4. Configuring /.ssh/config file https://nerderati.com/2011/03/17/simplify-your-life-with-an-ssh-config-file/


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