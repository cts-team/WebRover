<?php


namespace WebRover\Framework\Database;


use Doctrine\Common\Util\ClassUtils;
use WebRover\Framework\Support\Str;

/**
 * Class Model
 * @package WebRover\Framework\Database
 * @mixin QueryBuilder
 */
class Model
{
    /**
     * @var Manager
     */
    public static $resolver;

    protected $connection;

    protected $table;

    protected $primaryKey = 'id';

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        if (is_null($this->table)) {
            $shortName = ClassUtils::newReflectionObject($this)->getShortName();

            $this->table = Str::snake(lcfirst($shortName));
        }

        return $this->table;
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    private function query()
    {
        if (is_array($this->connection)) {
            self::$resolver->addConnection($this->connection);
        }

        return self::$resolver->builder($this->connection);
    }

    private function model()
    {
        return $this->query()->table($this->getTable());
    }
    
    /**
     * @return Manager
     */
    public function getResolver()
    {
        return self::$resolver;
    }

    /**
     * @param mixed $resolver
     */
    public static function setResolver(Manager $resolver)
    {
        self::$resolver = $resolver;
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->model(), $method], $parameters);
    }
}
