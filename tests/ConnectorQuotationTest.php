<?php

class ConnectorQuotationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \FinXLog\Module\Connector\Quotation
     */
    private $app;

    public function getApp()
    {
        if (!$this->app) {
            $this->app = new \FinXLog\Module\Connector\Quotation();
        }

        return $this->app;
    }

    public function test_connector()
    {
        $this->assertTrue($this->getApp()->getDefaultConnector() instanceof \Socket\Raw\Socket);
        $this->assertTrue($this->getApp()->getConnector() instanceof \Socket\Raw\Socket);
    }
    public function test_read()
    {
        $this->assertTrue(strlen($this->getApp()->read()) > 0);
        $result = $this->getApp()->getQuotation();
        $this->assertTrue(strlen($result) > 0);
        $this->assertNotFalse(
            preg_match(
                '~^([^;=]+=[^;=]+);([^;=]+=[^;=]+);([^;=]+=[^;=]+)$~u',
                $result
            ),
            'wrong result:' . $result
        );

        $result = FinXLog\Helper\Quotation::getFromString($result);
        $this->assertTrue(is_array($result));
        $this->assertTrue(count($result) == 3);
        $this->assertTrue($result === $result + ['T' => 1, 'B'=>2, 'S'=>3]);

    }

    public function test_setters()
    {

    }
}
