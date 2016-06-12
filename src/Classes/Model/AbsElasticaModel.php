<?php
namespace FinXLog\Model;
use Elastica\Connection;
use FinXLog\Iface;
use FinXLog\Module\Connector\Elastica;
use FinXLog\Traits;


class AbsElasticaModel extends AbsModel implements Iface\ElasticaConnector
{
    use Traits\WithConnector;

    protected $index;
    protected $type;
    protected $id;

    public function saveBulk(array $objects)
    {
        $result = [];
        foreach ($objects as $object) {
            $result[] = $this->getPreparedDocument($object);
        }

        $this->getDb()->addDocuments($result);

        return $this;
    }

    public function save($object)
    {
       return $this->saveBulk([$object]);
    }


    public function getPreparedDocument($object)
    {
        $document = $this
            ->getBlankDocument()
            ->setData($object);

        if ($this->id && isset($object[$this->id])) {
            $document->setId($object[$this->id]);
        }

        return $document;
    }

    /**
     * @return \Elastica\Document
     */
    public function getBlankDocument()
    {
        return (new \Elastica\Document())
            ->setIndex($this->getIndex())
            ->setType($this->getType());
    }


    public function getIndex()
    {
        return $this->index;
    }

    public function getType()
    {
        return $this->type;
    }

    protected function getDefaultParams()
    {
        $param = getenv('FINXLOG_ELASTICA_PARAM');
        assert(!empty($param));
        $param = json_decode($param, true);
        assert(!empty($param));

        return (array) $param;
    }

    public function getDefaultConnector()
    {
        return new Elastica();
    }

    /**
     * @return \Elastica\Client
     */
    public function getDb()
    {
        return $this->getConnector()->getConnector();
    }

    /**
     * @return \Elastica\Index
     */
    public function getElasticIndex()
    {
        return $this->getDb()
            ->getIndex($this->index);
    }

    //@todo for simple query
    //public function getResult(Query $query);

    /**
     * @return \Elastica\ResultSet
     */
    public function getResponse(\Elastica\Query $query)
    {
        return $this->getElasticIndex()
            ->search($query);
    }

    public function checkConnector(Iface\Connector $connector)
    {
        assert($connector->getConnector() instanceof \Elastica\Client);

        return $this;
    }

}