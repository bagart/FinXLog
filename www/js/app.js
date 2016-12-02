var FinXLog = {
    //namespace
    config: {},
    start: function() {
        //this.graph.start();
        this.ws.start();
        this.ws.subscribe('BTCUSD', 'M1', 'AAPL');
    },
    dbg: function(msg, obj) {
        if (!FinXLog.config.FINXLOG_DEBUG) return;
        console.log(msg);
        if (obj) console.log(obj);
    },
    ws: {
        conn:  null,
        subscribes: [
//          {type: 'subscribe', quotation: '_ALL', doji: null}
//          {type: 'subscribe', quotation: 'BTCUSD'}
//          {type: 'subscribe', quotation: 'BTCUSD', 'agg_type': 'AAPL', 'agg_period': 'H1'}
        ],
        query_onstart: [
            //prepare selector and build table
            {type: 'quotations'}
        ],
        start: function (){
            this.conn = new WebSocket(this.getWsUrl());
            this.conn.onopen = function() {
                var i;
                if (FinXLog.config.FINXLOG_DEBUG) {
                    FinXLog.dbg('WS: open');
                }
                for (i = 0; i < FinXLog.ws.query_onstart.length; ++i) {
                    FinXLog.ws.send(FinXLog.ws.query_onstart[i]);
                }
                for (i = 0; i < FinXLog.ws.subscribes.length; ++i) {
                    FinXLog.ws.send(FinXLog.ws.subscribes[i]);
                }
                FinXLog.ws.subscribe('BTCUSD', 'M1', 'AAPL');
            };

            this.conn.onmessage = this.onmessage;
        },
        /*
        message2graph: function (message) {
            var graph_data = {};
            for (var i = 0; i < message.quotations.length; ++i) {
                var quotation = message.quotations[i];
                FinXLog.dbg(quotation);
                if (typeof graph_data[quotation.S] == 'undefined') {
                    graph_data[quotation.S] = [];
                }
                graph_data[quotation.S].push({
                    name: quotation.S,
                    x: new Date(quotation.T).getTime(),
                    y: quotation.B
                });
            }

            return graph_data;
        },*/
        onmessage: function (request) {
            var message;
            try {
                message = JSON.parse(request.data);
                FinXLog.dbg('incoming:');
                FinXLog.dbg(message);
            } catch (err) {
                FinXLog.dbg('wrong incoming:');
                FinXLog.dbg(err);
                FinXLog.dbg(request.data);
                throw err;
            }

            switch (message.type) {
                case 'quotations': {
                    $(message.quotations).each(function(i, item) {
                        var opt = document.createElement('option');
                        opt.value = item;
                        opt.appendChild(document.createTextNode(item))
                        $('select[name="quotation"]').append(opt)
                    });
                    break;
                }
                case 'error': {
                    FinXLog.dbg(message);

                    break;
                }
                case 'subscribe': {
                    switch(message.agg_type) {
                        case 'AAPL': {
                            FinXLog.draw(message.quotations);
                        }
                        default: {
                            FinXLog.dbg('bot ready draw for:', message);
                        }
                    }
                    break;
                }
                default: {
                    FinXLog.dbg(message);
                }
            }
        },
        subscribe: function (subj, agg_period, agg_type, stop) {
            var request = {
                'type': stop ? 'unsubscribe' : 'subscribe',
                'quotation': subj,
                'agg_period': agg_period,
                'agg_type': agg_type
            };

            this.send(request);
            if (stop) {
                for (var i = 0; i < this.subscribes.length; ++i) {
                    if (JSON.stringify(FinXLog.ws.subscribes[i]) == JSON.stringify(request)) {
                        //@todo
                        //FinXLog.ws.subscribes.remove
                        console.log('remove:');
                        console.log(FinXLog.ws.subscribes[i]);
                    }
                }
            } else {
                this.subscribes.push(request)
            }
        },
        send: function (obj) {
            if (this.conn) {
                FinXLog.dbg('send:', obj);
                this.conn.send(JSON.stringify(obj));
            } else {
                FinXLog.dbg('skip send(!conn):', obj);
            }
        },
        getWsUrl: function ()
        {
            return 'ws://'
                + (
                    FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_HOST
                        ? FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_HOST
                        : FinXLog.config.FINXLOG_DOMAIN
                )
                + (
                    FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_PORT
                        ? ':' + FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_PORT
                        : ''
                )
                + FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_PATH ;
        }
    },
    graph: {
        list: [],
        default_list: [
            {
                quotation: 'EURUSD',
                opts: {},
                conn: null
            }
        ],
        start: function (){
            /*Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });*/
            for (var i = 0; i < FinXLog.graph.default_list.length; ++i) {
                var graph_cur = FinXLog.graph.default_list[i];
                graph_cur.opts = this.blank_opts;
                graph_cur.opts.title.text = 'Real Time ' + graph_cur.quotation;
                graph_cur.opts.yAxis.title.text = 'ratio';
                //graph_cur.conn = new Highcharts.Chart(graph_cur.opts);
                FinXLog.graph.list.push(graph_cur);
            }
        },
        blank_opts: {
            title: {text: 'Real Time quotations'},
            xAxis: {
                type: 'datetime',
                tickPixelInterval: 100
            },
            yAxis: {
                title: {text: 'Samples'},
                tickInterval: 10,
                min: 0,
                max: 100
            },
            tooltip: {
                formatter: function () {
                    return '<b>' + this.series.name + '</b><br/>'
                        + JSON.stringify([
                            Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.x),
                            this.y
                        ]);
                }
            },
            chart: {
                type: 'spline',
                    renderTo: 'graph_container',
                    events: {
                    load: function () {
                        FinXLog.dbg('chart load');
                    }
                }
            },
            series: []
        }
    },
    draw: function (data) {

        // split the data set into ohlc and volume
        var ohlc = [],
            volume = [],
            dataLength = data.length,
            // set the allowed units for data grouping
            groupingUnits = [[
                'week',                         // unit name
                [1]                             // allowed multiples
            ], [
                'month',
                [1, 2, 3, 4, 6]
            ]],
            i = 0;
        for (i; i < dataLength; i += 1) {
            ohlc.push([
                data[i][0], // the date
                data[i][1], // open
                data[i][2], // high
                data[i][3], // low
                data[i][4] // close
            ]);

            volume.push([
                data[i][0], // the date
                data[i][5] // the volume
            ]);
        }


        // create the chart
        $('#container').highcharts('StockChart', {

            rangeSelector: {
                selected: 1
            },

            title: {
                text: 'AAPL Historical'
            },

            yAxis: [{
                labels: {
                    align: 'right',
                    x: -3
                },
                title: {
                    text: 'OHLC'
                },
                height: '60%',
                lineWidth: 2
            }, {
                labels: {
                    align: 'right',
                    x: -3
                },
                title: {
                    text: 'Volume'
                },
                top: '65%',
                height: '35%',
                offset: 0,
                lineWidth: 2
            }],

            series: [{
                type: 'candlestick',
                name: 'AAPL',
                data: ohlc,
                dataGrouping: {
                    units: groupingUnits
                }
            }, {
                type: 'column',
                name: 'Volume',
                data: volume,
                yAxis: 1,
                dataGrouping: {
                    units: groupingUnits
                }
            }]
        });
    }
};

includeJS('js/config_autobuild.js');

function includeJS(url)
{
    var script = document.createElement('script');
    script.src = url;
    script.type = 'text/javascript';
    document.getElementsByTagName('head')[0].appendChild(script);
}
