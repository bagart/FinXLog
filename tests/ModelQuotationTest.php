<?php

class ModelQuotationTest extends PHPUnit_Framework_TestCase
{
    private $app;

    public function getApp()
    {
        if (!$this->app) {
            $this->app = new \FinXLog\Model\Quotation();
        }

        return $this->app;
    }

    public function test_basic()
    {
        $this->assertTrue($this->getApp() instanceof \FinXLog\Model\AbsModel);
        $this->assertTrue($this->getApp()->getDefaultConnector() instanceof \FinXLog\Module\Connector\Elastica);
        $this->assertTrue($this->getApp()->getConnector() instanceof \FinXLog\Module\Connector\Elastica);
        $this->assertTrue($this->getApp()->getDb() instanceof \Elastica\Client);
        $this->assertTrue($this->getApp()->getElasticIndex() instanceof \Elastica\Index);
        $this->assertTrue($this->getApp()->getPreparedDocument('string') instanceof \Elastica\Document);

        $this->assertTrue($this->getApp()->getIndex() == 'quotation');
        $this->assertTrue($this->getApp()->getType() == 'quotation');
    }
}
