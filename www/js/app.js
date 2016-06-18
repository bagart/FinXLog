Highcharts.setOptions({
    global: {
        useUTC: false
    }
});

var FinXLog = {
    config: {},
    start: function() {
        this.ws.setup();
        for (var i = 0; i < this.graph.length; ++i) {
            this.graph[i].setup();
        }
        this.ws.subscribe('_ALL');
    },
    dbg: function(msg, obj) {
        if (!FinXLog.config.FINXLOG_DEBUG) return;
        console.log(msg);
        if (obj) console.log(obj);
    },
    ws: {
        subscribes: [
            {type: 'subscribe', quotation: '_ALL', doji: null}
        ],
        query_onstart: [//just for non-static
            {type: 'quotations'}
        ],
        conn:  null,
        setup: function () {
            this.conn = new WebSocket(this.getWsUrl());
            this.conn.onopen = function() {
                if (FinXLog.config.FINXLOG_DEBUG) {
                    FinXLog.dbg('WS: open');
                }
                for (var i = 0; i < FinXLog.ws.query_onstart.length; ++i) {
                    FinXLog.ws.conn.send(FinXLog.ws.query_onstart[i]);
                }

                for (var i = 0; i < FinXLog.ws.subscribes.length; ++i) {
                    FinXLog.ws.send(FinXLog.ws.subscribes[i]);
                }
            };

            this.conn.onmessage = this.onmessage;
        },
        onmessage: function (request) {
            var message;
            try {
                message = JSON.parse(request);
                FinXLog.dbg('incoming:');
                FinXLog.dbg(message.data.type);
            } catch (err) {
                FinXLog.dbg('wrong incoming:');
                FinXLog.dbg(err);
                FinXLog.dbg(request.data);
                throw err
            }


            switch (message.data.type) {
                case 'quotations': {
                    for (var i = 0; i < msg.data.quotations.length; ++i) {
                        this.dbg(msg.data.quotations[i]);
                    }
                    break;
                }
            }
        },
        subscribe: function (subj, doji, stop) {
            var request = {
                'type': stop ? 'unsubscribe' : 'subscribe',
                'quotation': subj,
                'doji': doji ? doji : null
            };
            this.send(request);
            if (stop) {
                for (var i = 0; i < FinXLog.ws.subscribes.length; ++i) {
                    if (JSON.stringify(FinXLog.ws.subscribes[i]) == JSON.stringify(request)) {
                        //@todo
                        //FinXLog.ws.subscribes.remove
                        console.log('remove:');
                        console.log(FinXLog.ws.subscribes[i]);
                    }

                }
            } else {
                FinXLog.ws.subscribes.push(request)
            }
        },
        send: function (obj) {
            FinXLog.dbg('send:', obj);
            this.conn.send(JSON.stringify(obj));
        },
        getWsUrl: function ()
        {
            return 'ws://'
                + (FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_HOST ? FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_HOST : FinXLog.config.FINXLOG_DOMAIN)
                + (FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_PORT ? ':' + FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_PORT : '')
                + FinXLog.config.FINXLOG_WEBSOCKET_EXTARNAL_PATH ;
        }
    },
    graph: [
        {
            conn: null,
            setup: function () {
                this.conn = new Highcharts.Chart(this.graph_opts);
            },
            graph_opts: {
                title: {text: 'Real Time Samples'},
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
                            if (FinXLog.config.FINXLOG_DEBUG) {
                                console.log('chart load');
                            }
                        }
                    }
                },
                series: [{
                    name: 'tst',
                    data: (function () {
                        var data = [],
                            time = (new Date()).getTime(),
                            i;
                        for (i = -19; i <= 0; i++) {
                            data.push({
                                x: time + (i * 1000),
                                y: 0
                            });
                        }
                        return data;
                    })()
                }],
                draw: function (data) {
                    console.log('draw:');
                    console.log(data);
                }
            }
        }
    ]
};

includeJS('js/config_autobuild.js');

function includeJS(url)
{
    var script = document.createElement('script');
    script.src = url;
    script.type = 'text/javascript';
    document.getElementsByTagName('head')[0].appendChild(script);
}