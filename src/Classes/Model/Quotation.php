<?php
namespace FinXLog\Model;
use Elastica\Query;
use FinXLog\Traits;

class Quotation extends AbsElasticaModel
{
    protected $index = 'quotation';
    protected $type = 'quotation';
    protected $id = null;
    private $query_quotations = '{
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
      "size": 100,
      "sort": []
    }';

    public function getQuotations($quotation = null)
    {
        $query = json_decode($this->query_quotations, true);
        if (!$quotation) {
            $query['query']['bool']['must'] = [];
        } else {
            $query['query']['bool']['must'][0]['query_string']['query'] = $quotation;
        }

        return $this->getDocuments(new Query($query));
    }
}