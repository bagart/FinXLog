<?php

class HelperQuotationTest extends PHPUnit_Framework_TestCase
{

    public function test_auto()
    {
        $result = FinXLog\Helper\Quotation::getFromString('A=1;B=2;C=3');

        $this->assertTrue(count($result) == 3);
        $this->assertTrue($result === $result  + ['A'=>0, 'B'=>0, 'C'=>0]);
    }

}
