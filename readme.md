
# Install
```bash
#install requirements
apt-get install php7.0 composer beanstalkd postgresql-9.5 php7.0-pgsql 

# https://www.elastic.co/guide/en/elasticsearch/reference/current/setup-repositories.html
wget -qO - https://packages.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
echo "deb https://packages.elastic.co/elasticsearch/2.x/debian stable main" | sudo tee -a /etc/apt/sources.list.d/elasticsearch-2.x.list
sudo apt-get update && sudo apt-get install elasticsearch
# not critical but important: configure elasticsearch: name, ip, etc
#sudo nano /etc/elasticsearch/elasticsearch.yml

git clone https://github.com/bagart/FinXLog.git
cd FinXLog
composer update
```



# Using
Daemon for import quotation:

```bash
command/daemon/quotation_exchange2db.php
```

## Optional: use with AMQP Queue for exchange high traffic
important: direct import is more quickly, if server has free resource

```bash
command/daemon/quotation_exchange2amqp.php
command/daemon/quotation_amqp2db.php
```

## Optional replacement for quotation_load.php
is direct linux-way socket to amqp pipe for high performance
source: https://github.com/src-d/beanstool
### Install beanstool
```bash
wget https://github.com/src-d/beanstool/releases/download/v0.2.0/beanstool_v0.2.0_linux_amd64.tar.gz
tar -xvzf beanstool_v0.2.0_linux_amd64.tar.gz
sudo cp beanstool_v0.2.0_linux_amd64/beanstool /usr/local/bin/
```
### Run
@todo: make auto-restart on network error
```bash
command/daemon/quotation_exchange2amqp.sh
```

@todo: make quotation_exchange2db.sh
