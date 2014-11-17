<?php

namespace Codeception\Module;

use Codeception\Module;
use Codeception\Exception\Module as ModuleException;

class PropelModule extends Module
{
    /**
     * @var \Pdo
     */
    private $connection;

    public function _initialize()
    {
        $this->connection = \Propel::getConnection();
    }

    /**
     * @param array $settings
     */
    public function _beforeSuite($settings = array())
    {
        $this->connection->beginTransaction();
    }

    public function _afterSuite()
    {
        $this->connection->rollBack();
    }

    /**
     * @param string $table
     * @param string $selectColumns
     * @param array $criteria
     * @param int $limit
     * @return array
     * @throws ModuleException
     */
    public function seeInDatabase($table, $selectColumns, array $criteria, $limit = 10)
    {
        $criteriaColumns = array_keys($criteria);
        $criteriaConditions = array_values($criteria);
        $where = '';

        for ($i = 0; $i < count($criteriaColumns); $i++) {
            if ($i > 0) {
                $where .= ' AND ';
            }

            $where .= $criteriaColumns[$i] . ' = :param' . $i;
        }

        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s LIMIT %d',
            is_array($selectColumns) ? implode(', ', $selectColumns) : $selectColumns,
            $table,
            $where,
            $limit
        );

        $query = $this->connection->prepare($sql);
        $this->debugSection('Query: ', $sql);

        for ($i = 0; $i < count($criteriaConditions); $i++) {
            $query->bindValue(
                ':param' . $i,
                $criteriaConditions[$i],
                $this->guessParamType($criteriaConditions[$i])
            );
        }

        $query->execute();

        return $query->fetchAll();
    }

    /**
     * @param mixed $param
     * @return int
     * @throws ModuleException
     */
    protected function guessParamType($param)
    {
        if (!is_scalar($param)) {
            throw new ModuleException(
                __CLASS__,
                'Only scalar parameters are allowed for propel conditions'
            );
        }

        switch ($param) {
            case is_int($param):
                $type = \PDO::PARAM_INT;
                break;
            case is_bool($param):
                $type = \PDO::PARAM_BOOL;
                break;
            case is_float($param):
                $type = \PDO::PARAM_STR;
                break;
            default:
                $type = \PDO::PARAM_STR;
                break;
        }

        return $type;
    }
}
