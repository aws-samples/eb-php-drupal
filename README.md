# Deploying drupal on Elastic Beanstalk
Use the EB CLI to create an Elastic Beanstalk environment with an attached RDS DB and EFS file system to provide Drupal with a MySQL database and shared storage for uploaded files.

NOTE: Amazon EFS is not available in all AWS regions. Check the [Region Table](https://aws.amazon.com/about-aws/global-infrastructure/regional-product-services/) to see if your region is supported.

You can also run the database outside of the environment to decouple compute and database resources. See the Elastic Beanstalk Developer Guide for a tutorial with instructions that use an external DB instance: [Deploying a High-Availability Drupal Website with an External Amazon RDS Database to Elastic Beanstalk](https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/php-hadrupal-tutorial.html). The tutorial also uses the AWS Management Console instead of the EB CLI.

These instructions were tested with Drupal 8.5.3.

## Install the EB CLI

The EB CLI integrates with Git and simplifies the process of creating environments, deploying code changes, and connecting to the instances in your environment with SSH. You will perform all of these activites when installing and configuring Drupal.

If you have pip, use it to install the EB CLI.

```Shell
$ pip install --user --upgrade awsebcli
```

Add the local install location to your OS's path variable.

###### Linux
```Shell
$ export PATH=~/.local/bin:$PATH
```
###### OS-X
```Shell
$ export PATH=~/Library/Python/3.4/bin:$PATH
```
###### Windows
Add `%USERPROFILE%\AppData\Roaming\Python\Scripts` to your PATH variable. Search for **Edit environment variables for your account** in the Start menu.

If you don't have pip, follow the instructions [here](http://docs.aws.amazon.com/elasticbeanstalk/latest/dg/eb-cli3-install.html).


## Set up your project directory

1. Download Drupal.

        ~$ curl https://ftp.drupal.org/files/projects/drupal-8.5.3.tar.gz -o drupal.tar.gz

2. Download the configuration files in this repository

        ~$ wget https://github.com/aws-samples/eb-php-drupal/releases/download/v1.0/eb-php-drupal-v1.zip

3. Extract Drupal and change the name of the folder

        ~$ tar -xvf drupal.tar.gz
        ~$ mv drupal-8.5.3 drupal-beanstalk
        ~$ cd drupal-beanstalk

4. Extract the configuration files over the Drupal installation

        ~/drupal-beanstalk$ unzip ../eb-php-drupal-v1.zip
         creating: .ebextensions/
        inflating: .ebextensions/dev.config
        inflating: .ebextensions/drupal.config
        inflating: .ebextensions/efs-create.config
        inflating: .ebextensions/efs-mount.config
        inflating: .ebextensions/loadbalancer-sg.config
        inflating: LICENSE
        inflating: README.md
        inflating: beanstalk-settings.php


## Create an Elastic Beanstalk environment

1. Configure a local EB CLI repository with the PHP platform. Choose a [supported region](http://docs.aws.amazon.com/general/latest/gr/rande.html#elasticbeanstalk_region) that is close to you.

        ~/drupal-beanstalk$ eb init --platform php7.0 --region us-west-2
        Application drupal-beanstalk has been created.

2. Configure SSH. Create a key that Elastic Beanstalk will assign to the EC2 instances in your environment to allow you to connect to them later. You can also choose an existing key pair if you have the private key locally.

        ~/drupal-beanstalk$ eb init
        Do you want to set up SSH for your instances?
        (y/n): y

        Select a keypair.
        1) [ Create new KeyPair ]
        (default is 1): 1

        Type a keypair name.
        (Default is aws-eb): beanstalk-drupal

3. Create an Elastic Beanstalk environment with a MySQL database.

        ~/drupal-beanstalk$ eb create drupal-beanstalk --sample --database
        Enter an RDS DB username (default is "ebroot"):
        Enter an RDS DB master password:
        Retype password to confirm:
        Environment details for: drupal-beanstalk
          Application name: drupal-beanstalk
          Region: us-west-2
          Deployed Version: Sample Application
          Environment ID: e-nrx24yzgmw
          Platform: 64bit Amazon Linux 2016.09 v2.2.0 running PHP 7.0
          Tier: WebServer-Standard
          CNAME: UNKNOWN
          Updated: 2016-11-01 12:20:27.730000+00:00
        Printing Status:
        INFO: createEnvironment is starting.

## Networking configuration
Modify the configuration files in the .ebextensions folder with the IDs of your [default VPC and subnets](https://console.aws.amazon.com/vpc/home#subnets:filter=default), and [your public IP address](https://www.google.com/search?q=what+is+my+ip). 

 - `.ebextensions/efs-create.config` creates an EFS file system and mount points in each Availability Zone / subnet in your VPC. Identify your default VPC and subnet IDs in the [VPC console](https://console.aws.amazon.com/vpc/home#subnets:filter=default). If you have not used the console before, use the region selector to select the same region that you chose for your environment.

  ### WARNING: EFS lifecycle
  Any resources that you create with configuration files are tied to the lifecycle of your environment. They are lost if you terminate your environment or remove the configuration file.
  Use this configuration file to create an Amazon EFS file system in a development environment. When you no longer need the environment and terminate it, the file system is cleaned up for you.
  For production environments, consider creating the file system using Amazon EFS directly.
  For details, see [Creating an Amazon Elastic File System](http://docs.aws.amazon.com/efs/latest/ug/creating-using-create-fs.html).
 - `.ebextensions/ssh.config` restricts access to your environment to your IP address to protect it during the Drupal installation process. Replace the placeholder IP address near the top of the file with your public IP address.

## Deploy Drupal to your environment
Deploy the project code to your Elastic Beanstalk environment.

First, confirm that your environment is `Ready` with `eb status`. Environment creation takes about 15 minutes due to the RDS DB instance provisioning time.

```Shell
~/drupal-beanstalk$ eb status
~/drupal-beanstalk$ eb deploy
```

### NOTE: security configuration

This project includes a configuration file (`loadbalancer-sg.config`) that creates a security group and assigns it to the environment's load balancer, using the IP address that you configured in `dev.config` to restrict HTTP access on port 80 to connections from your network. Otherwise, an outside party could potentially connect to your site before you install Drupal and configure your admin account.

You can [view the related SGs in the EC2 console](https://console.aws.amazon.com/ec2/v2/home#SecurityGroups:search=drupal-beanstalk)

## Install Drupal

Open your site in a browser.

```Shell
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

```Shell
~/drupal-beanstalk$ cp beanstalk-settings.php sites/default/settings.php
```

This file reads variables for the database connection, which are provided by Elastic Beanstalk when you create a database instance inside your environment. It also reads variables named `HASH_SALT` and `SYNC_DIR`.

The hash salt can be any value but shouldn't be stored in source control. Use `eb setenv` to set this variable directly on the environment.

```Shell
~/drupal-beanstalk$ eb setenv HASH_SALT=sd082lboxi235kf9g8hah
```

The sync directory is not a secret but is randomly generated when you install Drupal. Connect to the instance to find the value for this variable.

```Shell
~/drupal-beanstalk$ eb ssh
[ec2-user ~]$ tail /var/app/current/sites/default/settings.php
  $config_directories['sync'] = 'sites/default/files/config_4ccfX2sPQm79p1mk5IbUq9S_FokcENO4mxyC-L18-4g_xKj_7j9ydn31kDOYOgnzMu071Tvc4Q/sync';
```

Replace the placeholder value in `.ebextensions/drupal.config` with the value shown after `$config_directories['sync']`.

```Shell
    SYNC_DIR: sites/default/files/config_XXXXXXXXXXXXXXX/sync
```

Remove the custom load balancer configuration to open the site to the Internet.

```Shell
~/drupal-beanstalk$ rm .ebextensions/loadbalancer-sg.config
~/drupal-beanstalk$ eb deploy
```

Finally, scale up to run the site on multiple instances for high availability.

```Shell
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
