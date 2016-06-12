<?php
namespace FinXLog\Model;
use Elastica\Aggregation;
use Elastica\Query;
use FinXLog\Traits;

class QuotationAgg extends Quotation
{
    /*
     * more test, any first, not avg first
        "first":{"top_hits":{"size": 1,"sort":[{"T": {"order": "asc"}}]}}
        w/o deviation: extended_stats => stats
     */
    private $query_doji = '{
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
     * get DOJI by exchange subject (japanese candlesticks)
     * [date][min,max,first,last]
     * @param string $subject exchange subject(EURUSD, USDBTC)
     * @param string $interval (period)
     * @return mixed
     */
    public function getDoji($subject, $interval = 'day')
    {
        return $this->getResult(
            $this->getDojiQuery(
                $subject,
                $interval
            )
        );
    }

    /**
     * return
     * @param Query $query
     * @return array
     */
    public function getResult(Query $query)
    {
        return current( //1st agg name is not important
            $this->getResponse($query)
                ->getAggregations()
        )['buckets'];
    }

    public function getDojiQuery($subject, $interval = 'day')
    {
        $query = json_decode($this->query_doji, true);

        $query['query']['bool']['must'][0]['query_string']['query'] = $subject;
        $query['aggs']['date']['date_histogram']['interval'] = $interval;

        return new Query($query);
    }
}
