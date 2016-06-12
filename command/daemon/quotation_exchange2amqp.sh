#!/bin/bash
FINXLOG_ROOT_DIR=`dirname $0`/../../;
#import .env
export $(cat $FINXLOG_ROOT_DIR.env | grep -P '^FINXLOG_(QUOTATION|AMQP)')

#export | grep FIN

# netcat > amqp
while :
do
    netcat $FINXLOG_QUOTATION_SERVER_ADDRESS $FINXLOG_QUOTATION_SERVER_PORT | xargs -I {} beanstool put -t=$FINXLOG_AMQP_TUBE_QUOTATION -b {}
done