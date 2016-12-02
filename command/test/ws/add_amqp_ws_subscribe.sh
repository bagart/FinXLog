#!/bin/bash
FINXLOG_ROOT_DIR=`dirname $0`/../../../;
#import .env
export $(cat $FINXLOG_ROOT_DIR.env | grep -P '^FINXLOG_(QUOTATION|AMQP)')

beanstool put -t=$FINXLOG_AMQP_TUBE_WS -b '{"quotation":"BTCUSD"}';
beanstool put -t=$FINXLOG_AMQP_TUBE_WS -b '{"quotation":"BTCUSD","agg_type":"AAPL","agg_period":"M1"}';
