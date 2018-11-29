# Build Notes


## Steps to setup the Server 
1. Build all images before running docker-compose up
2. Create a tempoarary php container to install a new laravel app using: (note: $(pwd) -- this represents the root directory of the project itself not the application)
```
run --rm -it -v $(pwd):/opt koledachris/php-fpm:1.0.0 bash -c "cd /opt && composer create project --prefer-dist laravel/laravel test-app" 
```


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