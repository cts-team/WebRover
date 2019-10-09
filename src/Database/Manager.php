<?php


namespace WebRover\Framework\Database;


use Doctrine\DBAL\DBALException;

/**
 * Class Manager
 * @package WebRover\Framework\Database
 */
class Manager
{
    private $links = [];

    private $params = [];

    /**
     * @var string
     */
    protected $wrapperClass = 'Doctrine\DBAL\Connections\MasterSlaveConnection';

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @param $params
     * @param null $name
     * @return Builder
     * @throws DBALException
     */
    public function connect($params)
    {
        $identifier = $this->identifier($params);

        if (isset($this->links[$identifier])) {
            $connection = $this->links[$identifier];
        } else {
            $connection = $this->getConnectionInstance($params);
        }

        return $this->builder($connection);
    }

    public function addConnection($params, $name = null)
    {
        $identifier = $this->identifier($name ?: $params);

        $connection = $this->getConnectionInstance($params);

        $this->links[$identifier] = $connection;
    }

    public function table($name, $alias = null)
    {
        $builder = $this->builder();

        return $builder->table($name, $alias);
    }

    public function getConnection($connectionName = null)
    {
        if (is_null($connectionName)) {
            $connectionName = $this->getDefaultConnectionName();
        }
        
        $connectionName = $this->identifier($connectionName);

        if (!isset($this->links[$connectionName])) {
            $connections = $this->params['connections'];

            $this->addConnection($connections[$connectionName], $connectionName);
        }

        return $this->links[$connectionName];
    }

    public function getDefaultConnection()
    {
        return $this->getConnection();
    }

    /**
     * @return string
     */
    public function getDefaultConnectionName()
    {
        return $this->params['default'];
    }

    public function builder($connectionName = null)
    {
        if ($connectionName instanceof Connection) {
            $connection = $connectionName;
        } else {
            $connection = $this->getConnection($connectionName);
        }

        return new Builder($connection);
    }

    private function identifier($params)
    {
        if (is_array($params)) {
            ksort($params);
            $identifier = md5(serialize($params));
        } else {
            $identifier = $params;
        }

        return $identifier;
    }

    private function getConnectionInstance(array $params)
    {
        if (isset($params['master']) && isset($params['slave'])) {
            if (!isset($params['wrapperClass'])) {
                $params['wrapperClass'] = $this->wrapperClass;
            }
        }

        if (!isset($params['driverOptions'])) $params['driverOptions'] = [];

        if (array_key_exists(\PDO::ATTR_STRINGIFY_FETCHES, $params['driverOptions']) === false) {
            $params['driverOptions'][\PDO::ATTR_STRINGIFY_FETCHES] = false;
        }

        if (array_key_exists(\PDO::ATTR_EMULATE_PREPARES, $params['driverOptions']) === false) {
            $params['driverOptions'][\PDO::ATTR_EMULATE_PREPARES] = false;
        }

        $params['charset'] = 'UTF8';

        return new Connection($params);
    }

    public function __call($method, $params)
    {
        if (!in_array($method, [
            'connect',
            'addConnection',
            'table',
            'getConnection',
            'getDefaultConnection',
            'setDefaultConnectionName',
            'getDefaultConnectionName'
        ])) {
            return call_user_func_array([$this->builder(), $method], $params);
        }

        return call_user_func_array([$this, $method], $params);
    }
}
