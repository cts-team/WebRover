<?php


namespace WebRover\Framework\Database;


use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Index;

/**
 * Class Table
 * @package WebRover\Framework\Database
 */
class Table
{
    private $connection;

    private $tableName;

    public function __construct(Connection $connection, $tableName)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    /**
     * 获取所有列
     *
     * @return Column[]
     */
    public function getColumns()
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->getColumn();
    }

    /**
     * 检查列是否存在
     *
     * @param $columnName
     * @return bool
     */
    public function hasColumn($columnName)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->hasColumn($columnName);
    }

    /**
     * 获取列
     *
     * @param $columnName
     * @return Column
     */
    public function getColumn($columnName)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->getColumn($columnName);
    }

    /**
     * 添加列
     *
     * @param $columnName
     * @param $type
     * @param array $options
     * @return $this
     */
    public function addColumn($columnName, $type, array $options = [])
    {
        $this->dropColumn($columnName);

        list($originalTable, $clonedTable) = $this->getTable();

        $clonedTable->addColumn($columnName, $type, $options);
        $this->alter($originalTable, $clonedTable);

        return $this;
    }

    /**
     * 更改列
     *
     * @param $columnName
     * @param array $options
     * @return $this
     */
    public function changeColumn($columnName, array $options)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        if ($clonedTable->hasColumn($columnName)) {
            $clonedTable->changeColumn($columnName, $options);
            $this->alter($originalTable, $clonedTable);
        }

        return $this;
    }

    /**
     * 删除列
     *
     * @param $columnName
     * @return $this
     */
    public function dropColumn($columnName)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        if ($clonedTable->hasColumn($columnName)) {
            $clonedTable->dropColumn($columnName);
            $this->alter($originalTable, $clonedTable);
        }

        return $this;
    }

    /**
     * 获取主键
     *
     * @return Index|null
     */
    public function getPrimaryKey()
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->getPrimaryKey();
    }

    /**
     * 检查是否存在主键
     *
     * @return bool
     */
    public function hasPrimaryKey()
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->hasPrimaryKey();
    }

    /**
     * 获取主键列
     *
     * @return array
     */
    public function getPrimaryKeyColumns()
    {
        list($originalTable, $clonedTable) = $this->getTable();

        $columns = [];

        if ($clonedTable->hasPrimaryKey()) {
            $columns = $clonedTable->getPrimaryKeyColumns();
        }

        return $columns;
    }

    /**
     * 设置主键
     *
     * @param array $columns
     * @param bool $indexName
     * @return $this
     */
    public function setPrimaryKey(array $columns, $indexName = false)
    {
        $this->dropPrimaryKey();

        list($originalTable, $clonedTable) = $this->getTable();

        $clonedTable->setPrimaryKey($columns, $indexName);

        $this->alter($originalTable, $clonedTable);

        return $this;
    }

    /**
     * 删除主键
     *
     * @return $this
     */
    public function dropPrimaryKey()
    {
        list($originalTable, $clonedTable) = $this->getTable();

        if ($clonedTable->hasPrimaryKey()) {
            $clonedTable->dropPrimaryKey();
            $this->alter($originalTable, $clonedTable);
        }

        return $this;
    }

    /**
     * 获取所有索引
     *
     * @return Index[]
     */
    public function getIndexes()
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->getIndexes();
    }

    /**
     * 获取指定索引
     *
     * @param $indexName
     * @return Index|null
     */
    public function getIndex($indexName)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        if (!$clonedTable->hasIndex($indexName)) {
            return null;
        }

        return $clonedTable->getIndex($indexName);
    }

    /**
     * 检查是否存在指定索引
     *
     * @param $indexName
     * @return bool
     */
    public function hasIndex($indexName)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->hasIndex($indexName);
    }

    /**
     * 添加索引
     *
     * @param array $columnNames
     * @param null $indexName
     * @param array $flags
     * @param array $options
     * @return $this
     */
    public function addIndex(array $columnNames, $indexName = null, array $flags = [], array $options = [])
    {
        $this->dropIndex($indexName);

        list($originalTable, $clonedTable) = $this->getTable();

        $clonedTable->addIndex($columnNames, $indexName, $flags, $options);

        $this->alter($originalTable, $clonedTable);

        return $this;
    }

    /**
     * 添加唯一索引
     *
     * @param array $columnNames
     * @param null $indexName
     * @param array $options
     * @return $this
     */
    public function addUniqueIndex(array $columnNames, $indexName = null, array $options = [])
    {
        $this->dropIndex($indexName);

        list($originalTable, $clonedTable) = $this->getTable();

        $clonedTable->addUniqueIndex($columnNames, $indexName, $options);

        $this->alter($originalTable, $clonedTable);

        return $this;
    }

    /**
     * 重命名索引
     *
     * @param $oldIndexName
     * @param null $newIndexName
     * @return $this
     */
    public function renameIndex($oldIndexName, $newIndexName = null)
    {
        $this->dropIndex($newIndexName);

        list($originalTable, $clonedTable) = $this->getTable();

        if ($clonedTable->hasIndex($oldIndexName)) {
            $clonedTable->renameIndex($oldIndexName, $newIndexName);
            $this->alter($originalTable, $clonedTable);
        }

        return $this;
    }

    /**
     * 删除索引
     *
     * @param $indexName
     * @return $this
     */
    public function dropIndex($indexName)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        if ($clonedTable->hasIndex($indexName)) {
            $clonedTable->dropIndex($indexName);
            $this->alter($originalTable, $clonedTable);
        }

        return $this;
    }

    /**
     * 检查索引是否按给定列的顺序开始
     *
     * @param array $columnsNames
     * @return bool
     */
    public function columnsAreIndexed(array $columnsNames)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->columnsAreIndexed($columnsNames);
    }

    /**
     * 获取所有选项
     *
     * @return array
     */
    public function getOptions()
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->getOptions();
    }

    /**
     * 获取指定选项
     *
     * @param $name
     * @return mixed
     */
    public function getOption($name)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->getOption($name);
    }

    /**
     * 检查指定选项是否存在
     *
     * @param $name
     * @return bool
     */
    public function hasOption($name)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        return $clonedTable->getOption($name);
    }

    /**
     * 添加选项
     *
     * @param $name
     * @param $value
     * @return $this
     */
    public function addOption($name, $value)
    {
        list($originalTable, $clonedTable) = $this->getTable();

        $clonedTable->addOption($name, $value);

        $this->alter($originalTable, $clonedTable);

        return $this;
    }

    /**
     * 修改表
     *
     * @param $originalTable
     * @param $clonedTable
     * @return $this
     */
    protected function alter($originalTable, $clonedTable)
    {
        $comparator = new Comparator();
        $tableDiff = $comparator->diffTable($originalTable, $clonedTable);
        $sm = $this->connection->getDoctrine()->getSchemaManager();
        $sm->alterTable($tableDiff);
        return $this;
    }

    /**
     * 获取表
     *
     * @return array
     */
    private function getTable()
    {
        $sm = $this->connection->getDoctrine()->getSchemaManager();
        $table = $sm->listTableDetails($this->tableName);
        $cloned = clone $table;

        return [$table, $cloned];
    }
}