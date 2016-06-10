<?php
namespace FinXLog\Model;
use FinXLog\Traits;

class Quotation extends AbsElasticoModel
{
    protected $index = 'quotation';
    protected $type = 'quotation';
    protected $id = null;
}