<?php
namespace FinXLog\Iface;

interface ElasticaConnector extends Connector
{
    public function saveBulk(array $objects);
    public function save($object);
    public function getPreparedDocument($object);

    /**
     * @return \Elastica\Document
     */
    public function getBlankDocument();

    public function getIndex();

    public function getType();

    public function getDefaultConnector();

    /**
     * @return \Elastica\Client
     */
    public function getDb();
}