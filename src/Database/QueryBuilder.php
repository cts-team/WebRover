<?php


namespace WebRover\Framework\Database;


use Closure;
use Doctrine\DBAL\SQLParserUtils;

/**
 * Class QueryBuilder
 * @package WebRover\Framework\Database
 */
class QueryBuilder
{
    use ConnectionTrait;

    private $connection;

    private $query;

    private $table;

    private $alias;

    public function __construct(Connection $connection, $table, $alias = null)
    {
        $this->connection = $connection;

        $this->doctrine = $connection->getDoctrine();

        $this->query = $this->newQuery();

        $this->table = $table;

        $this->alias = $alias;
    }

    public function insert(array $data, array $types = [])
    {
        return $this->doctrine->insert($this->table, $data, $types);
    }

    public function insertGetId(array $data, array $types = [])
    {
        $this->insert($data, $types);

        return $this->doctrine->lastInsertId();
    }

    public function update(array $data, array $types = [])
    {
        $query = $this->query
            ->update($this->table, $this->alias);

        $columnList = $params = [];

        foreach ($data as $columnName => $value) {
            $columnList[] = $columnName;

            $query->set($columnName, '?');

            $params[] = $value;
        }

        $prevSql = $query->getSQL();

        $prevParams = $this->getParameters();

        $prevTypes = $this->getParameterTypes();

        list($sql, $prevParams, $prevTypes) = SQLParserUtils::expandListParameters($prevSql, $prevParams, $prevTypes);

        $params = array_merge($params, $prevParams);

        return $this->doctrine->executeUpdate($sql, $params, $types);
    }

    public function delete()
    {
        return $this->query
            ->delete($this->table, $this->alias)
            ->execute();
    }

    public function fetch($column = ['*'])
    {
        return $this->query
            ->select($column)
            ->from($this->table, $this->alias)
            ->execute()
            ->fetch();
    }

    public function fetchAll($column = ['*'])
    {
        return $this->query
            ->select($column)
            ->from($this->table, $this->alias)
            ->execute()
            ->fetchAll();
    }

    public function touch(Closure $closure)
    {
        $this->query->from($this->table, $this->alias);

        if (!$this->query->getQueryPart('select')) {
            $this->query->select(['*']);
        }

        $query = $this->getSQL();

        $params = $this->getParameters();

        return $this->doctrine->project($query, $params, $closure);
    }

    public function project($query, array $params, Closure $function)
    {
        return $this->doctrine->project($query, $params, $function);
    }

    public function column($column)
    {
        return $this->fetchAll($column);
    }

    public function where($predicates)
    {
        $this->query->where($predicates);

        return $this;
    }

    public function andWhere($where)
    {
        $this->query->andWhere($where);

        return $this;
    }

    public function orWhere($where)
    {
        $this->query->orWhere($where);

        return $this;
    }

    public function from($from, $alias = null)
    {
        $this->query->from($from, $alias);

        return $this;
    }

    public function leftJoin($fromAlias, $join, $alias, $condition = null)
    {
        $this->query->leftJoin($fromAlias, $join, $alias, $condition);

        return $this;
    }

    public function rightJoin($fromAlias, $join, $alias, $condition = null)
    {
        $this->query->rightJoin($fromAlias, $join, $alias, $condition);

        return $this;
    }

    public function join($fromAlias, $join, $alias, $condition = null)
    {
        $this->query->join($fromAlias, $join, $alias, $condition);

        return $this;
    }

    public function getType()
    {
        return $this->query->getType();
    }

    public function getState()
    {
        return $this->query->getState();
    }

    public function getSql()
    {
        return $this->query->getSQL();
    }

    public function setParameter($key, $value, $type = null)
    {
        $this->query->setParameter($key, $value, $type);

        return $this;
    }

    public function setParameters(array $params, array $types = [])
    {
        $this->query->setParameters($params, $types);

        return $this;
    }

    public function getParameters()
    {
        return $this->query->getParameters();
    }

    public function getParameter($key)
    {
        return $this->query->getParameter($key);
    }

    public function getParameterTypes()
    {
        return $this->query->getParameterTypes();
    }

    public function getParameterType($key)
    {
        return $this->query->getParameterType($key);
    }

    public function offset($value)
    {
        $this->query->setFirstResult($value);

        return $this;
    }

    public function getOffset()
    {
        return $this->query->getFirstResult();
    }

    public function limit($value)
    {
        $this->query->setMaxResults($value);

        return $this;
    }

    public function getLimit()
    {
        return $this->query->getMaxResults();
    }

    public function groupBy($value)
    {
        $this->query->groupBy($value);

        return $this;
    }

    public function addGroupBy($value)
    {
        $this->query->addGroupBy($value);

        return $this;
    }

    public function having($value)
    {
        $this->query->having($value);

        return $this;
    }

    public function andHaving($value)
    {
        $this->query->andHaving($value);

        return $this;
    }

    public function orHaving($value)
    {
        $this->query->orHaving($value);

        return $this;
    }

    public function orderBy($sort, $order = null)
    {
        $this->query->orderBy($sort, $order);

        return $this;
    }

    public function addOrderBy($sort, $order = null)
    {
        $this->query->addOrderBy($sort, $order);

        return $this;
    }

    public function count($column = '*')
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    public function avg($column)
    {
        return (float)$this->aggregate(__FUNCTION__, $column);
    }

    public function sum($column)
    {
        return (int)$this->aggregate(__FUNCTION__, $column);
    }

    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    private function aggregate($aggregate, $column)
    {
        $select = $aggregate . '(' . $column . ')';

        return $this->query
            ->select($select)
            ->from($this->table, $this->alias)
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }

    private function newQuery()
    {
        return new \Doctrine\DBAL\Query\QueryBuilder($this->connection->getDoctrine());
    }
}
