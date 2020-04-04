<?php


namespace WebRover\Framework\Database;


use Closure;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\CacheException;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;

/**
 * Trait ConnectionTrait
 * @package WebRover\Framework\Database
 */
trait ConnectionTrait
{
    /**
     * @var Connection
     */
    private $doctrine;

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->doctrine->getDatabase();
    }

    /**
     * @return string|null
     */
    public function getHost()
    {
        return $this->doctrine->getHost();
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->doctrine->getPort();
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->doctrine->getUsername();
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->doctrine->getPassword();
    }

    /**
     * @return Driver
     */
    public function getDriver()
    {
        return $this->doctrine->getDriver();
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->doctrine->getConfiguration();
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->doctrine->getEventManager();
    }

    /**
     * @return AbstractPlatform
     */
    public function getDatabasePlatform()
    {
        return $this->doctrine->getDatabasePlatform();
    }

    /**
     * Sets auto-commit mode for this connection.
     *
     * If a connection is in auto-commit mode, then all its SQL statements will be executed and committed as individual
     * transactions. Otherwise, its SQL statements are grouped into transactions that are terminated by a call to either
     * the method commit or the method rollback. By default, new connections are in auto-commit mode.
     *
     * NOTE: If this method is called during a transaction and the auto-commit mode is changed, the transaction is
     * committed. If this method is called and the auto-commit mode is not changed, the call is a no-op.
     *
     * @param boolean $autoCommit True to enable auto-commit mode; false to disable it.
     *
     * @see   isAutoCommit
     */
    public function setAutoCommit($autoCommit)
    {
        $this->doctrine->setAutoCommit($autoCommit);
    }

    /**
     * Sets the fetch mode.
     *
     * @param integer $fetchMode
     *
     * @return void
     */
    public function setFetchMode($fetchMode)
    {
        $this->doctrine->setFetchMode($fetchMode);
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $statement The SQL query.
     * @param array $params The query parameters.
     * @param array $types The query parameter types.
     *
     * @return array
     */
    public function fetchAssoc($statement, array $params = [], array $types = [])
    {
        return $this->doctrine->fetchAssoc($statement, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @param string $statement The SQL query to be executed.
     * @param array $params The prepared statement params.
     * @param array $types The query parameter types.
     *
     * @return array
     */
    public function fetchArray($statement, array $params = [], array $types = [])
    {
        return $this->doctrine->fetchArray($statement, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string $statement The SQL query to be executed.
     * @param array $params The prepared statement params.
     * @param integer $column The 0-indexed column number to retrieve.
     * @param array $types The query parameter types.
     *
     * @return mixed
     */
    public function fetchColumn($statement, array $params = [], $column = 0, array $types = [])
    {
        return $this->doctrine->fetchColumn($statement, $params, $column, $types);
    }

    /**
     * Whether an actual connection to the database is established.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->doctrine->isConnected();
    }

    /**
     * Checks whether a transaction is currently active.
     *
     * @return boolean TRUE if a transaction is currently active, FALSE otherwise.
     */
    public function isTransactionActive()
    {
        return $this->doctrine->isTransactionActive();
    }

    /**
     * Executes an SQL DELETE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table on which to delete.
     * @param array $identifier The deletion criteria. An associative array containing column-value pairs.
     * @param array $types The types of identifiers.
     *
     * @return integer The number of affected rows.
     *
     * @throws InvalidArgumentException
     */
    public function delete($tableExpression, array $identifier, array $types = [])
    {
        return $this->doctrine->delete($tableExpression, $identifier, $types);
    }

    /**
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
        $this->doctrine->close();
    }

    /**
     * Sets the transaction isolation level.
     *
     * @param integer $level The level to set.
     *
     * @return integer
     */
    public function setTransactionIsolation($level)
    {
        return $this->doctrine->setTransactionIsolation($level);
    }

    /**
     * Gets the currently active transaction isolation level.
     *
     * @return integer The current transaction isolation level.
     */
    public function getTransactionIsolation()
    {
        return $this->doctrine->getTransactionIsolation();
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to update quoted or unquoted.
     * @param array $data An associative array containing column-value pairs.
     * @param array $identifier The update criteria. An associative array containing column-value pairs.
     * @param array $types Types of the merged $data and $identifier arrays in that order.
     *
     * @return integer The number of affected rows.
     */
    public function update($tableExpression, array $data, array $identifier, array $types = [])
    {
        return $this->doctrine->update($tableExpression, $data, $identifier, $types);
    }

    /**
     * Inserts a table row with specified data.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to insert data into, quoted or unquoted.
     * @param array $data An associative array containing column-value pairs.
     * @param array $types Types of the inserted data.
     *
     * @return integer The number of affected rows.
     */
    public function insert($tableExpression, array $data, array $types = [])
    {
        return $this->doctrine->insert($tableExpression, $data, $types);
    }

    /**
     * Quotes a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * NOTE: Just because you CAN use quoted identifiers does not mean
     * you SHOULD use them. In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str The name to be quoted.
     *
     * @return string The quoted name.
     */
    public function quoteIdentifier($str)
    {
        return $this->doctrine->quoteIdentifier($str);
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed $input The parameter to be quoted.
     * @param string|null $type The type of the parameter.
     *
     * @return string The quoted parameter.
     */
    public function quote($input, $type = null)
    {
        return $this->doctrine->quote($input, $type);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $sql The SQL query.
     * @param array $params The query parameters.
     * @param array $types The query parameter types.
     *
     * @return array
     */
    public function fetchAll($sql, array $params = [], $types = [])
    {
        return $this->doctrine->fetchAll($sql, $params, $types);
    }

    /**
     * Prepares an SQL statement.
     *
     * @param string $statement The SQL statement to prepare.
     *
     * @return Statement The prepared statement.
     *
     * @throws DBALException
     */
    public function prepare($statement)
    {
        return $this->doctrine->prepare($statement);
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     *
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     * @param string $query The SQL query to execute.
     * @param array $params The parameters to bind to the query, if any.
     * @param array $types The types the previous parameters are in.
     * @param QueryCacheProfile|null $qcp The query cache profile, optional.
     * @return Statement
     * @throws DBALException
     */
    public function executeQuery($query, array $params = array(), $types = array(), QueryCacheProfile $qcp = null)
    {
        return $this->doctrine->executeQuery($query, $params, $types, $qcp);
    }

    /**
     * Executes a caching query.
     *
     * @param string $query The SQL query to execute.
     * @param array $params The parameters to bind to the query, if any.
     * @param array $types The types the previous parameters are in.
     * @param QueryCacheProfile $qcp The query cache profile.
     *
     * @return ResultStatement
     *
     * @throws CacheException
     */
    public function executeCacheQuery($query, $params, $types, QueryCacheProfile $qcp)
    {
        return $this->doctrine->executeCacheQuery($query, $params, $types, $qcp);
    }

    /**
     * Executes an, optionally parametrized, SQL query and returns the result,
     * applying a given projection/transformation function on each row of the result.
     *
     * @param string $query The SQL query to execute.
     * @param array $params The parameters, if any.
     * @param Closure $function The transformation function that is applied on each row.
     *                           The function receives a single parameter, an array, that
     *                           represents a row of the result set.
     *
     * @return array The projected result of the query.
     */
    public function project($query, array $params, Closure $function)
    {
        return $this->doctrine->project($query, $params, $function);
    }

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @param array $args
     * @return Statement
     *
     * @throws DBALException
     */
    public function query(...$args)
    {
        return $this->doctrine->query(...$args);
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
     * and returns the number of affected rows.
     *
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string $query The SQL query.
     * @param array $params The query parameters.
     * @param array $types The parameter types.
     *
     * @return integer The number of affected rows.
     *
     * @throws DBALException
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        return $this->doctrine->executeUpdate($query, $params, $types);
    }

    /**
     * Executes an SQL statement and return the number of affected rows.
     *
     * @param string $statement
     *
     * @return integer The number of affected rows.
     *
     * @throws DBALException
     */
    public function exec($statement)
    {
        return $this->doctrine->exec($statement);
    }

    /**
     * Returns the current transaction nesting level.
     *
     * @return integer The nesting level. A value of 0 means there's no active transaction.
     */
    public function getTransactionNestingLevel()
    {
        return $this->doctrine->getTransactionNestingLevel();
    }

    /**
     * Fetches the SQLSTATE associated with the last database operation.
     *
     * @return integer The last error code.
     */
    public function errorCode()
    {
        return $this->doctrine->errorCode();
    }

    /**
     * Fetches extended error information associated with the last database operation.
     *
     * @return array The last error information.
     */
    public function errorInfo()
    {
        return $this->doctrine->errorInfo();
    }

    /**
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers,
     * because the underlying database may not even support the notion of AUTO_INCREMENT/IDENTITY
     * columns or sequences.
     *
     * @param string|null $seqName Name of the sequence object from which the ID should be returned.
     *
     * @return string A string representation of the last inserted ID.
     */
    public function lastInsertId($seqName = null)
    {
        return $this->doctrine->lastInsertId($seqName);
    }

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this Connection instance as an (optional) parameter.
     *
     * If an exception occurs during execution of the function or transaction commit,
     * the transaction is rolled back and the exception re-thrown.
     *
     * @param Closure $func The function to execute transactionally.
     *
     * @return void
     *
     * @throws Exception
     */
    public function transactional(Closure $func)
    {
        $this->doctrine->transactional($func);
    }

    /**
     * Sets if nested transactions should use savepoints.
     *
     * @param boolean $nestTransactionsWithSavepoints
     *
     * @return void
     *
     * @throws ConnectionException
     */
    public function setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints)
    {
        $this->doctrine->setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints);
    }

    /**
     * Gets if nested transactions should use savepoints.
     *
     * @return boolean
     */
    public function getNestTransactionsWithSavepoints()
    {
        return $this->doctrine->getNestTransactionsWithSavepoints();
    }

    /**
     * Starts a transaction by suspending auto-commit mode.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->doctrine->beginTransaction();
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     *
     * @throws ConnectionException If the commit failed due to no active transaction or
     *                                            because the transaction was marked for rollback only.
     */
    public function commit()
    {
        $this->doctrine->commit();
    }

    /**
     * Cancels any database changes done during the current transaction.
     *
     * This method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventListener methods.
     *
     * @throws ConnectionException If the rollback operation failed.
     */
    public function rollBack()
    {
        $this->doctrine->rollBack();
    }

    /**
     * Creates a new savepoint.
     *
     * @param string $savepoint The name of the savepoint to create.
     *
     * @return void
     *
     * @throws ConnectionException
     */
    public function createSavepoint($savepoint)
    {
        $this->doctrine->createSavepoint($savepoint);
    }

    /**
     * Releases the given savepoint.
     *
     * @param string $savepoint The name of the savepoint to release.
     *
     * @return void
     *
     * @throws ConnectionException
     */
    public function releaseSavepoint($savepoint)
    {
        $this->doctrine->releaseSavepoint($savepoint);
    }

    /**
     * Rolls back to the given savepoint.
     *
     * @param string $savepoint The name of the savepoint to rollback to.
     *
     * @return void
     *
     * @throws ConnectionException
     */
    public function rollbackSavepoint($savepoint)
    {
        $this->doctrine->rollbackSavepoint($savepoint);
    }

    /**
     * Gets the wrapped driver connection.
     *
     * @return \Doctrine\DBAL\Driver\Connection
     */
    public function getWrappedConnection()
    {
        return $this->doctrine->getWrappedConnection();
    }

    /**
     * Marks the current transaction so that the only possible
     * outcome for the transaction to be rolled back.
     *
     * @return void
     *
     * @throws ConnectionException If no transaction is active.
     */
    public function setRollbackOnly()
    {
        $this->doctrine->setRollbackOnly();
    }

    /**
     * Checks whether the current transaction is marked for rollback only.
     *
     * @return boolean
     *
     * @throws ConnectionException If no transaction is active.
     */
    public function isRollbackOnly()
    {
        return $this->doctrine->isRollbackOnly();
    }

    /**
     * Converts a given value to its database representation according to the conversion
     * rules of a specific DBAL mapping type.
     *
     * @param mixed $value The value to convert.
     * @param string $type The name of the DBAL mapping type.
     *
     * @return mixed The converted value.
     */
    public function convertToDatabaseValue($value, $type)
    {
        return $this->doctrine->convertToDatabaseValue($value, $type);
    }

    /**
     * Converts a given value to its PHP representation according to the conversion
     * rules of a specific DBAL mapping type.
     *
     * @param mixed $value The value to convert.
     * @param string $type The name of the DBAL mapping type.
     *
     * @return mixed The converted type.
     */
    public function convertToPHPValue($value, $type)
    {
        return $this->doctrine->convertToPHPValue($value, $type);
    }

    /**
     * Resolves the parameters to a format which can be displayed.
     *
     * @param array $params
     * @param array $types
     *
     * @return array
     * @internal This is a purely internal method. If you rely on this method, you are advised to
     *           copy/paste the code as this method may change, or be removed without prior notice.
     *
     */
    public function resolveParams(array $params, array $types)
    {
        return $this->doctrine->resolveParams($params, $types);
    }

    /**
     * @return bool
     */
    public function ping()
    {
        return $this->doctrine->ping();
    }
}
