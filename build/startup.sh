#! /bin/bash

# Switch user to root
sudo su

# Install necessary system services
yum update -y && \
yum install -y git && \
amazon-linux-extras install docker && \
service docker start  && \
systemctl enable docker && \
usermod -aG docker ec2-user && \
curl -L https://github.com/docker/compose/releases/download/1.23.0-rc3/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose && \
chmod +x /usr/local/bin/docker-compose && \

# If there is no www-data user create
# checking group existence https://stackoverflow.com/questions/29073210/how-to-check-if-a-group-exists-and-add-if-it-doesnt-in-linux-shell-script
if [ -z $( grep www-data /etc/group) ]
  then
    useradd -ru 1001 -U www-data  ## create a www-data system user -r and complimentary group -U of same name
fi

# Then add the user to the www-data group
usermod -aG www-data ec2-user && \
groupmod -g 1001 www-data 

# If a deployer user doesn't exist create one 
if [ -z $( grep deployer /etc/passwd) ]
  then
    useradd deployer
fi

# Add deployer to www-data group
usermod -aG www-data deployer 

# If no key file exists for the deployer user then create one
# note that you can only switch to this user account if you are already operating as root. This is because the deployer account was not created with a pw
if [ ! -e /home/deployer/.ssh/id_rsa ]
  then 
    su deployer 
    ssh-keygen -o -t rsa -b 4096 -f /home/deployer/.ssh/id_rsa -q -N ""
fi

# reboot