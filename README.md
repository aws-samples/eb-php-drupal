# Deploying drupal on Elastic Beanstalk
These instructions were tested with Drupal 8.2.2

## Install the EB CLI

The EB CLI integrates with Git and simplifies the process of creating environments, deploying code changes, and connecting to the instances in your environment with SSH. You will perform all of these activites when installing and configuring Drupal.

If you have pip, use it to install the EB CLI.

```
$ pip install --user --upgrade awsebcli
$ export PATH=~/.local/bin:$PATH
```

If you don't have pip, follow the instructions [here](http://docs.aws.amazon.com/elasticbeanstalk/latest/dg/eb-cli3-install.html).

## Download and extract Drupal and the configuration files

```
~$ curl https://ftp.drupal.org/files/projects/drupal-8.2.2.tar.gz -o drupal.tar.gz
~$ curl https://github.com/awslabs/eb-php-drupal/releases/download/v1.0/eb-php-drupal-v1.zip -o eb-php-drupal.zip
~$ tar -xvf drupal.tar.gz && mv drupal-8.2.2 drupal-beanstalk && cd drupal-beanstalk
~/drupal-beanstalk$ unzip ../eb-php-drupal.zip
```

## Create an environment
~/drupal-beanstalk$ eb init --platform php5.6 --region us-east-2
(specify a different region if you have a preference)
~/drupal-beanstalk$ eb ssh --setup
~/drupal-beanstalk$ eb create drupal-beanstalk --sample --database
(choose database username/password, CTRL+C to exit once create is in-progress)

## Networking configuration
Modify the configuration files in the .ebextensions folder with the IDs of your [default VPC and subnets](https://console.aws.amazon.com/vpc/home#subnets:filter=default), and [your public IP address](https://www.google.com/search?q=what+is+my+ip). 

 - `.ebextensions/efs-create.config` creates an EFS file system and mount points in each Availability Zone / subnet in your VPC.
 - `.ebextensions/ssh.config` restricts access to your environment to your IP address to protect it during the Drupal installation process.

## Deploy Drupal to your environment
Deploy the project code to your Elastic Beanstalk environment. 

First, confirm that your environment is `Ready` with `eb status`. Environment creation takes about 15 minutes due to the RDS DB instance provisioning time.

```
  $ eb status
  $ eb deploy
```

### NOTE: security configuration

This project includes a configuration file (`loadbalancer-sg.config`) that creates a security group and assigns it to the environment's load balancer, using the IP address that you configured in `ssh.config` to restrict HTTP access on port 80 to connections from your network. Otherwise, an outside party could potentially connect to your site before you install Drupal and configure your admin account.

You can [view the related SGs in the EC2 console](https://console.aws.amazon.com/ec2/v2/home#SecurityGroups:search=drupal-beanstalk)

## Install Drupal

Open your site in a browser.

```
~/drupal-beanstalk$ eb open
```

You are redirected to the Drupal installation wizard because the site has not been configured yet.

Perform a standard installation with the following settings for the database:

 - Database name: `ebdb`
 - Database user and password: values that you entered during `eb create`
 - Advanced > database endpoint: The RDS endpoint listed in the [Beanstalk console](https://console.aws.amazon.com/elasticbeanstalk/home#/application/overview?applicationName=drupal-beanstalk) under `drupal-beanstalk` > `Configuration` > `Data Tier`, not including the port number.

Installation takes about a minute to complete.

# Save the site settings to source

The installation process created a file named `settings.php` in the `sites/default` folder on the instance. You need this file in your source code to avoid resetting your site on subsequent deployments, but the file currently contains secrets that you don't want to commit to source.

The project includes a settings file that uses environment variables to provide secrets to the application. Create a copy of this file named `settings.php`.

```
~/drupal-beanstalk$ cp beanstalk-settings.php sites/default/settings.php
```

This file reads variables for the database connection, which are provided by Elastic Beanstalk when you create a database instance inside your environment. It also reads variables named `HASH_SALT` and `SYNC_DIR`.

The hash salt can be any value but shouldn't be stored in source control. Use `eb setenv` to set this variable directly on the environment.
```
~/drupal-beanstalk$ eb setenv HASH_SALT=randomnumbersandletters89237492374
```

The sync directory is not a secret but is randomly generated when you install Drupal. Connect to the instance to find the value for this variable. Then, replace the placeholder value in `.ebextensions/drupal.config` with the correct value.
```
~/drupal-beanstalk$ eb ssh
[ec2-user ~]$ tail /var/app/current/sites/default/settings.php
[ec2-user ~]$ exit
```

Remove the custom load balancer configuration to open the site to the Internet.
```
~/drupal-beanstalk$ rm .ebextensions/loadbalancer-sg.config
~/drupal-beanstalk$ eb deploy
```

Finally, scale up to run the site on multiple instances for high availability.
```
~/drupal-beanstalk$ eb scale 3
```

When the configuration update completes, open the site.
```
~/drupal-beanstalk$ eb open
```

Refresh the site several times to verify that all instances are reading from the EFS file system. Create posts and upload files to confirm functionality.

# Backup

Now that you've gone through all the trouble of installing your site, you will want to back up the data in RDS and EFS that your site depends on. See the following topics for instructions.

 - [DB Instance Backups](http://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/Overview.BackingUpAndRestoringAmazonRDSInstances.html)
 - [Back Up an EFS File System](http://docs.aws.amazon.com/efs/latest/ug/efs-backup.html)
