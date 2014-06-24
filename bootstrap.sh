#!/usr/bin/env bash

# install packages -- mysql not really needed as we use sqllite right now
# ruby is used for sass/compass
apt-get update
apt-get install -y apache2 libapache2-mod-php5 php5 php5-sqlite php5-mysql php-pear php-apc php5-curl php5-gd npm ruby-full rubygems1.8

sudo gem install  sass 
sudo gem install compass

# link shared folder to /var/www
rm -rf /var/www
ln -fs /vagrant /var/www

# apache config
a2enmod php5
a2enmod rewrite

sed -i '/AllowOverride None/c AllowOverride All' /etc/apache2/sites-available/default

# See: http://jeremykendall.net/2013/08/09/vagrant-synced-folders-permissions
sed -i 's/APACHE_RUN_USER=www-data/APACHE_RUN_USER=vagrant/' /etc/apache2/envvars
sed -i 's/APACHE_RUN_GROUP=www-data/APACHE_RUN_GROUP=vagrant/' /etc/apache2/envvars
chown -R vagrant:www-data /var/lock/apache2

service apache2 restart


# download bolt distribution
cd /vagrant/
if [ ! -d bolt ]; then
mkdir bolt
cd bolt
echo "Downloading bolt..."
wget http://bolt.cm/distribution/bolt_latest.tgz -O - | tar -xzvf  -
rm -rf app/config
cd ..
ln  -s `pwd`/config `pwd`/bolt/app/config
ln  -s `pwd`/theme `pwd`/bolt/theme/mafmc

fi

cd theme
# 
npm install

# bower
sudo npm install -g bower
bower install

echo "Bolt should be up and running at http://localhost:10000/bolt
