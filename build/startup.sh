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

# Set permissions on the serve directory 
chown -R ec2-user:www-data /srv && \
chmod -R +2770 /srv

# If a deployer user doesn't exist create one 
if [ -z $( grep deployer /etc/passwd) ]
  then
    useradd deployer && \ 
    usermod -aG www-data deployer
    usermod -aG docker deployer
fi

# Next switch to deployer user
# note that you can only switch to this user account if you are already operating as root. This is because the deployer account was not created with a pw  
su deployer

# Add docker-compose path to path for deployer user
export PATH=/usr/local/bin:$PATH

# Pull ssh key from S3,

# If no key file exists for the deployer user then create one and push to s3
if [ ! -e /home/deployer/.ssh/id_rsa ]
  then 
    ssh-keygen -o -t rsa -b 4096 -f /home/deployer/.ssh/id_rsa -q -N "" # possibly pull ssh key down from s3 or something this way each new server doesn't have to generate it
    chmod 600 /home/deployer/.ssh/id_rsa
fi

# If no ssh key exists to log in as the deployer user create one
# if [! -e /home/deployer/.ssh/authorized_keys ]
#   then
#     ssh-keygen -o -t rsa -b 4096 -f /home/deployer/.ssh/authorized_keys -q -N ""
#     chmod 600 /home/deployer/.ssh/authorized_keys 
# fi 

# reboot