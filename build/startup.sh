#! /bin/bash

sudo yum update -y && \
sudo yum install -y docker git && \
sudo service docker start  && \
sudo systemctl enable docker && \
sudo usermod -aG docker ec2-user && \
sudo curl -L https://github.com/docker/compose/releases/download/1.23.0-rc3/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose && \
sudo chmod +x /usr/local/bin/docker-compose && \
## create www-data user as 1001, set ec2-user to be member of www-data group
sudo useradd -ru 1001 -U www-data && \  ## create a www-data system user -r and complimentary group -U of same name
sudo usermod -aG www-data ec2-user && \
sudo groupmod -g 1001 www-data && \
export PATH="$PATH:/usr/local/bin" && \

cd /srv/ && sudo git clone git@gitlab.com:koleda/1up-forum.git && \
cd /srv/1up-forum/build && docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml up -d && \
cd /srv/1up-forum/levelup-forum/ && \
sudo docker run --rm -it -v $(pwd):/opt -w /opt koledachris/php-fpm:0.3.0 composer install && \
sudo docker run --rm -it -v $(pwd):/opt -w /opt koledachris/node:0.1.0 bash -c "apt-get update -y && apt-get install -y nodejs npm && npm install" && \
sudo docker run --rm -it -v $(pwd):/opt -w /opt koledachris/node:0.1.0 bash -c "apt-get update -y && apt-get install -y nodejs npm && npm rebuild node-sass && npm run production" && \
sudo docker run --rm -it  --network appnet -v $(pwd):/opt -w /opt koledachris/php-fpm:0.3.0 php artisan migrate && \
## set permissions 
sudo chown -R ec2-user:ec2-user /srv/ && \
sudo chown -R www-data:www-data /srv/1up-forum/levelup-forum/  && \
sudo find /srv/ -type d -exec chmod 750 {} + && \
sudo find /srv/ -type f -exec chmod 640 {} + && \
sudo find /srv/1up-forum/levelup-forum/ -type d -exec chmod 2770 {} + # the first 2 signifies all files created in a directory will belong to the owner of the directory