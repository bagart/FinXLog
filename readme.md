v 0.9.1
Complete: 
 - import
 - filter
 - save
 - queue
 - elastic log
 - highcharts AAPL with websocket

Instruments:
 - ElasticSearch for quick big data search
 - Composer, PSR-4
 - Monolog
 - ".ENV"  environment

Optional:
 - BeanstalkD(AMQP Queue manager). reason for use: quick delivery for "any" load with single stream(bash-scrpit)

@todo
 - simple json API 
 - switch quottion and period
maby:
 - Ratchet + WAMP + ZMQ
 - sql db
 - split traffic for parallel import ( % n )
 
# Install
```bash
#install requirements
sudo apt-get install php7.0 composer beanstalkd postgresql-9.5 php7.0-pgsql php7.0-mbstring

#optional. cur. not ready 
#sudo apt-get install php7.0-dev php-pear
#all questions - ENTER
#sudo pecl install event

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

-------------------------------
# Using
Daemon for import quotation:

```bash
command/daemon/import/quotation_exchange2db.php
```

## Optional: import with AMQP for scaling high traffic
important: direct import is more quickly, if server has free resource
important: direct import has minimal guarantee for stable: 
    mem leak
    elastic can go to repair node with slow insert
    crush the process leads to a loss of traffic
AMQP is depend by high performance, scalable beanstalk (and opensource client)


## Required(curently - ): WebSocket
WebSocket is require AMQP
    or need implement async elasticsearch client with guzzle or reactphp/http-client 
    or need implement async reactphp/child-process
not ready but simple:
    AMQP+AJAX+API can work without WebSocket
```bash
#load daemons can work on other servers with multiple fork
command/daemon/import/quotation_amqp2db.php
command/daemon/import/quotation_amqp2db.php fail
```



## Default exchange IMPORT (not best choice)
```bash
#run only one daemons
command/daemon/import/quotation_exchange2amqp.php
```

## Optional exchange IMPORT for hight traffic BASH replacement for quotation_load.php
is direct linux-way socket to amqp pipe for high performance
source: https://github.com/src-d/beanstool
### Install beanstool
```bash
wget https://github.com/src-d/beanstool/releases/download/v0.2.0/beanstool_v0.2.0_linux_amd64.tar.gz
tar -xvzf beanstool_v0.2.0_linux_amd64.tar.gz
sudo cp beanstool_v0.2.0_linux_amd64/beanstool /usr/local/bin/
```
## run
```bash
command/daemon/quotation_exchange2amqp.sh
```
