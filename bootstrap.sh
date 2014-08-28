#!/usr/bin/env bash

echo ===========================
echo Installing Packages
echo ===========================
sudo add-apt-repository -y ppa:chris-lea/node.js 


# install packages -- mysql not really needed as we use sqllite right now
# ruby is used for sass/compass
apt-get update
apt-get install -y apache2 libapache2-mod-php5 php5 php5-sqlite php5-mysql php-pear php-apc php5-curl php5-gd nodejs ruby ruby-dev git

echo ===========================
echo Installing gems
echo ===========================
sudo gem install  sass  --no-ri --no-rdoc
sudo gem install compass --no-ri --no-rdoc

echo ===========================
echo Setting up apache
echo ===========================
# link shared folder to /var/www
rm -rf /var/www
mkdir -p /var/www
ln -fs /vagrant /var/www/html

# apache config
a2enmod php5
a2enmod rewrite

sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# See: http://jeremykendall.net/2013/08/09/vagrant-synced-folders-permissions
sed -i 's/APACHE_RUN_USER=www-data/APACHE_RUN_USER=vagrant/' /etc/apache2/envvars
sed -i 's/APACHE_RUN_GROUP=www-data/APACHE_RUN_GROUP=vagrant/' /etc/apache2/envvars
chown -R vagrant:www-data /var/lock/apache2

service apache2 restart

echo ===========================
echo Setting up Bolt
echo ===========================
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
echo ===========================
echo Installing npm packages
echo ===========================
npm install

# bower
echo ===========================
echo installing bower, grunt
echo ===========================
sudo npm install -g bower grunt-cli

echo ===========================
echo bower install
echo ===========================
HOME=/home/vagrant sudo -u vagrant bower install -V --config.interactive=false

echo ===========================
echo updating css
echo ===========================
sudo -u vagrant grunt compass



echo ===========================
echo Bolt should be up and running at http://localhost:10000/bolt
echo ===========================
