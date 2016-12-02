# Description
this daemons to work with Browser WebSocket request from JS_APP

## 1st part 

get quotations list(or agg like doji)

JS_App
->WebSocket (client)
->daemon: ws2client (listen)
->AMQP
->daemon: amqp2ws (listen)
->Elasticsearch
//back
    ->WebSocket (service)
    ->daemon: ws2client (listen)
    ->WebSocket (all)
    ->JS_App
        ->table
        ->JS_HighCharts



@todo:
2nd part
daemon: 
import/ex2amqp (listen)
->AMQP
->import/amqp2db
    ->2db
    ->WebSocket (service)
    ->daemon: ws2client (listen)
    ->WebSocket (update)
    ->JS_App
        ->table
        ->JS_HighCharts
    