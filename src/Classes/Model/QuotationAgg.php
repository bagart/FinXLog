<?php
namespace FinXLog\Model;
use Elastica\Aggregation;
use Elastica\Query;
use FinXLog\Traits;

class QuotationAgg extends Quotation
{
    /**
     * @todo period deep
     * @var array
     */
    protected $agg_period = [
        'M1' => '60',
        'M5' => '300',
        'H1' => '3600',
        'D1' => '86400',
        'W1' => '604800',
    ];

    /*
     * more test, any first, not avg first
        "first":{"top_hits":{"size": 1,"sort":[{"T": {"order": "asc"}}]}}
        w/o deviation: extended_stats => stats
     */
    private $query_agg_period = '{
      "query": {
        "bool": {
          "must": [
            {
              "query_string": {
                "default_field": "S",
                "query": "BTCUSD"
              }
            }
          ]
        }
      },
      "from": 0,
      "size": 0,
      "sort": [],
      "aggs": {
        "date": {
          "date_histogram": {
            "min_doc_count": 1,
            "field": "T",
            "interval": "1d",
            "order": {
              "_key": "desc"
            }
          },
          "aggs": {
            "stat": {
              "extended_stats": {
                "field": "B",
                "sigma": 3
              }
            },
            "last": {
              "terms": {
                "size": 1,
                "field": "T",
                "order": {
                  "_term": "desc"
                }
              },
              "aggs": {
                "avg": {
                  "avg": {
                    "field": "B"
                  }
                }
              }
            },
            "first": {
              "terms": {
                "size": 1,
                "field": "T",
                "order": {
                  "_term": "asc"
                }
              },
              "aggs": {
                "avg": {
                  "avg": {
                    "field": "B"
                  }
                }
              }
            }
          }
        }
      }
    }';

    /**
     * @param string $subject exchange subject(EURUSD, USDBTC)
     * @param string $interval (period)
     * @return mixed
     */
    public function getAgg($subject, $interval = 'day')
    {
        return $this->getAggregations(
            $this->getDojiQuery(
                $subject,
                $interval
            )
        );
    }

    /**
     * AAPL (doji) historical OHLC data like the Google Finance API
     * [date, open, high, low, close]
     * @param $subject
     * @param string $interval
     * @return array
     */
    public function getAAPL($subject, $interval = 'M1')
    {
        $prepared_result = [];
        foreach ($this->getAgg($subject, $interval) as $agg) {
            $prepared_result[] = [
                1000 * strtotime($agg['key_as_string']),
                (float) $agg['first']['buckets'][0]['avg']['value'],
                (float) $agg['stat']['max'],
                (float) $agg['stat']['min'],
                (float) $agg['last']['buckets'][0]['avg']['value'],
            ];
        }

        return $prepared_result;
    }

    /**
     * return
     * @param Query $query
     * @return array
     */
    public function getAggregations(Query $query)
    {
        return current( //1st agg name is not important
            $this->getResponse($query)
                ->getAggregations()
        )['buckets'];
    }

    public function getDojiQuery($subject, $interval = 'day')
    {
        $query = json_decode($this->query_agg_period, true);
        $query['query']['bool']['must'][0]['query_string']['query'] = $subject;
        $query['aggs']['date']['date_histogram']['interval'] =
            isset($this->agg_period[$interval])
                ? $this->agg_period[$interval] . 's'
                : $interval;

        return new Query($query);
    }

    public function getAggPeriod()
    {
        return $this->agg_period;
    }
}
