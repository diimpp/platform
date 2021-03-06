<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;

/**
 * Calculates total count of query records
 */
class QueryCountCalculator
{
    /**
     * Calculates total count of query records
     *
     * @param Query $query
     * @return integer
     */
    public static function calculateCount(Query $query)
    {
        /** @var QueryCountCalculator $instance */
        $instance = new static();

        return $instance->getCount($query);
    }

    /**
     * Calculates total count of query records
     * Notes: this method do not make any modifications of the given query
     *
     * @param Query $query
     * @return integer
     */
    public function getCount(Query $query)
    {
        $parser            = new Parser($query);
        $parserResult      = $parser->parse();
        $parameterMappings = $parserResult->getParameterMappings();
        list($sqlParameters, $parameterTypes) = $this->processParameterMappings($query, $parameterMappings);

        $statement = $query->getEntityManager()->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM (' . $query->getSQL() . ') AS e',
            $sqlParameters,
            $parameterTypes
        );
        $result    = $statement->fetchColumn();

        return $result ? (int)$result : 0;
    }

    /**
     * @param Query $query
     * @param array $paramMappings
     * @return array
     * @throws QueryException
     */
    protected function processParameterMappings(Query $query, $paramMappings)
    {
        $sqlParams = array();
        $types     = array();

        /** @var Parameter $parameter */
        foreach ($query->getParameters() as $parameter) {
            $key = $parameter->getName();

            if (!isset($paramMappings[$key])) {
                throw QueryException::unknownParameter($key);
            }

            $value = $query->processParameterValue($parameter->getValue());
            $type  = ($parameter->getValue() === $value)
                ? $parameter->getType()
                : Query\ParameterTypeInferer::inferType($value);

            foreach ($paramMappings[$key] as $position) {
                $types[$position] = $type;
            }

            $sqlPositions = $paramMappings[$key];
            $value        = array($value);
            $countValue   = count($value);

            for ($i = 0, $l = count($sqlPositions); $i < $l; $i++) {
                $sqlParams[$sqlPositions[$i]] = $value[($i % $countValue)];
            }
        }

        if (count($sqlParams) != count($types)) {
            throw QueryException::parameterTypeMismatch();
        }

        if ($sqlParams) {
            ksort($sqlParams);
            $sqlParams = array_values($sqlParams);

            ksort($types);
            $types = array_values($types);
        }

        return array($sqlParams, $types);
    }
}
