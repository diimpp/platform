<?php

namespace Oro\Bundle\AddressBundle\Provider\ImportExport;

use Doctrine\Common\Persistence\ObjectManager;

class DbReader implements ReaderInterface
{
    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var int batch size for reading
     */
    protected $batchSize = 10;

    /**
     * @var int offset
     */
    protected $offset = 0;

    /**
     * @var int|null
     */
    protected $limit = 100;

    /**
     * @var string
     */
    protected $class;

    /**
     * @param string $class
     * @param ObjectManager $om
     * @param null|int $batchSize
     * @param null $limit
     */
    public function __construct($class, ObjectManager $om, $batchSize = null, $limit = null)
    {
        $metadata = $om->getClassMetadata($class);
        $this->class = $metadata->getName();
        $this->om = $om;

        if (!is_null($batchSize)) {
            $this->batchSize = $batchSize;
        }

        if (!is_null($limit)) {
            $this->limit = $limit;
        }
    }

    /**
     * @inheritdoc
     */
    public function readBatch()
    {
        $offset = $this->offset * $this->batchSize;
        $this->offset++;

        return $this->om->getRepository($this->class)->findBy(array(), array(), $this->limit, $offset);
    }

    public function reset()
    {
        $this->offset = 0;
    }

    public function getBatchSize()
    {
        return $this->batchSize;
    }

    public function getLimit()
    {
        return $this->limit;
    }
}
