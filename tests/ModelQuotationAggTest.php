<?php

class ModelQuotationAggTest extends PHPUnit_Framework_TestCase
{
    private $app;

    public function getApp()
    {
        if (!$this->app) {
            $this->app = new \FinXLog\Model\QuotationAgg();
        }

        return $this->app;
    }

    public function test_basic()
    {
        $this->assertTrue($this->getApp() instanceof \FinXLog\Model\AbsModel);
        $this->assertTrue($this->getApp()->getIndex() == 'quotation');
        $this->assertTrue($this->getApp()->getType() == 'quotation');
    }

    public function test_query()
    {
        $this->assertTrue($this->getApp()->getDojiQuery('q') instanceof \Elastica\Query);
        $query = $this->getApp()->getDojiQuery('q')->toArray();
        $this->assertTrue(is_array($query));
        $this->assertTrue(!empty($query['query']['bool']['must'][0]['query_string']['default_field']));

    }

    public function test_getAgg()
    {
        try {
            $this->assertTrue(is_array($this->getApp()->getAgg('BTCUSD')));
           } catch (\Elastica\Exception\ConnectionException $e) {
            //connection error
        } catch (Throwable $e) {
            $this->assertTrue(false, '!getDoji');
        }
    }
}
