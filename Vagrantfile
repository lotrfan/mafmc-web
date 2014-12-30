Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty64"
  config.vm.provision :shell, path: "bootstrap.sh"
  config.vm.network :forwarded_port, host: 10000, guest: 80

  # http://askubuntu.com/questions/238040/how-do-i-fix-name-service-for-vagrant-client 
  config.vm.provider "virtualbox" do |v| 
    v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
  end

  # see http://serverfault.com/questions/495914/vagrant-slow-internet-connection-in-guest
  # config.vm.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
  # config.vm.customize ["modifyvm", :id, "--natdnsproxy1", "on"]

end
