<?php
namespace FinXLog\Model;
use FinXLog\Traits;

class Quotation extends AbsElasticaModel
{
    protected $index = 'quotation';
    protected $type = 'quotation';
    protected $id = null;
}