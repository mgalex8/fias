<?php

declare(strict_types=1);

namespace marvin255\fias\tests\service\db;

use marvin255\fias\tests\DbTestCase;
use marvin255\fias\mapper\SqlMapperInterface;
use marvin255\fias\mapper\AbstractMapper;
use marvin255\fias\mapper\field;
use marvin255\fias\service\db\PdoConnection;
use marvin255\fias\service\db\Exception;
use PHPUnit\DbUnit\DataSet\CompositeDataSet;
use PDO;
use PDOException;
use PDOStatement;
use InvalidArgumentException;

/**
 * Тест для объекта, который взаймодействует с базой данных mysql.
 */
class PdoConnectionTest extends DbTestCase
{
    /**
     * Проверяет, что объект получает данные из таблицы указанной в маппере.
     */
    public function testSelectRow()
    {
        $tableName = 'testSelectRow';
        $columnsNames = [
            'id',
            'row1',
            'row2',
        ];
        $columnsDefinitions = [
            new field\IntNumber,
            new field\Line,
            new field\Line,
        ];
        $columns = array_combine($columnsNames, $columnsDefinitions);
        $id = 3;

        $mapper = $this->getMockBuilder(AbstractMapper::class)
            ->setMethods(['getMap', 'getSqlName', 'getSqlPrimary'])
            ->getMock();
        $mapper->method('getMap')->will($this->returnValue($columns));
        $mapper->method('getSqlName')->will($this->returnValue($tableName));
        $mapper->method('getSqlPrimary')->will($this->returnValue([reset($columnsNames)]));

        $mysql = new PdoConnection($this->getPdo(), 2);
        $selectedData = $mysql->selectRow($mapper, ['id' => $id]);
        $mysql->complete();

        $this->assertSame([
            'id' => $id,
            'row1' => 'row 3 1',
            'row2' => 'row 3 2',
        ], $selectedData);
    }

    /**
     * Проверяет, что объект добавляет новые записи в таблицу, указанную в маппере.
     */
    public function testInsert()
    {
        $tableName = 'testInsert';
        $columnsNames = [
            'id',
            'row1',
            'row2',
        ];
        $columnsDefinitions = [
            new field\IntNumber,
            new field\Line,
            new field\Line,
        ];
        $columns = array_combine($columnsNames, $columnsDefinitions);

        $mapper = $this->getMockBuilder(AbstractMapper::class)
            ->setMethods(['getMap', 'getSqlName', 'getSqlPrimary'])
            ->getMock();
        $mapper->method('getMap')->will($this->returnValue($columns));
        $mapper->method('getSqlName')->will($this->returnValue($tableName));
        $mapper->method('getSqlPrimary')->will($this->returnValue([reset($columnsNames)]));

        $mysql = new PdoConnection($this->getPdo(), 2);
        $mysql->insert($mapper, ['id' => 3, 'row1' => 'row 3 1', 'row2' => 'row 3 2']);
        $mysql->insert($mapper, ['id' => 4, 'row1' => 'row 4 1', 'row2' => 'row 4 2']);
        $mysql->insert($mapper, ['id' => 5, 'row1' => 'row 5 1', 'row2' => 'row 5 2']);
        $mysql->complete();

        $queryTable = $this->getConnection()->createQueryTable(
            $tableName,
            'SELECT * FROM ' . $tableName
        );
        $expectedTable = $this->createXmlDataSet(__DIR__ . '/_fixture/testInsert_expected.xml')
            ->getTable($tableName);

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    /**
     * Проверяет, что объект обновляет записи из таблицы, указанной в маппере.
     */
    public function testUpdate()
    {
        $tableName = 'testUpdate';
        $columnsNames = [
            'id',
            'row1',
            'row2',
        ];
        $columnsDefinitions = [
            new field\IntNumber,
            new field\Line,
            new field\Line,
        ];
        $columns = array_combine($columnsNames, $columnsDefinitions);

        $mapper = $this->getMockBuilder(AbstractMapper::class)
            ->setMethods(['getMap', 'getSqlName', 'getSqlPrimary'])
            ->getMock();
        $mapper->method('getMap')->will($this->returnValue($columns));
        $mapper->method('getSqlName')->will($this->returnValue($tableName));
        $mapper->method('getSqlPrimary')->will($this->returnValue([reset($columnsNames)]));

        $mysql = new PdoConnection($this->getPdo());
        $mysql->update($mapper, ['id' => 2, 'row2' => 'updated 2']);
        $mysql->update($mapper, ['id' => 3, 'row2' => 'updated 3']);
        $mysql->complete();

        $queryTable = $this->getConnection()->createQueryTable(
            $tableName,
            'SELECT * FROM ' . $tableName
        );
        $expectedTable = $this->createXmlDataSet(__DIR__ . '/_fixture/testUpdate_expected.xml')
            ->getTable($tableName);

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    /**
     * Проверяет, что объект удаляет записи из таблицы, указанной в маппере.
     */
    public function testDelete()
    {
        $tableName = 'testDelete';
        $columnsNames = [
            'id',
            'row1',
        ];
        $columnsDefinitions = [
            new field\IntNumber,
            new field\Line,
        ];
        $columns = array_combine($columnsNames, $columnsDefinitions);

        $mapper = $this->getMockBuilder(AbstractMapper::class)
            ->setMethods(['getMap', 'getSqlName', 'getSqlPrimary'])
            ->getMock();
        $mapper->method('getMap')->will($this->returnValue($columns));
        $mapper->method('getSqlName')->will($this->returnValue($tableName));
        $mapper->method('getSqlPrimary')->will($this->returnValue([reset($columnsNames)]));

        $mysql = new PdoConnection($this->getPdo());
        $mysql->delete($mapper, ['id' => 1]);
        $mysql->delete($mapper, ['id' => 3]);
        $mysql->complete();

        $queryTable = $this->getConnection()->createQueryTable(
            $tableName,
            'SELECT * FROM ' . $tableName
        );
        $expectedTable = $this->createXmlDataSet(__DIR__ . '/_fixture/testDelete_expected.xml')
            ->getTable($tableName);

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    /**
     * Проверяет, что объект выбросит исключение, если во входящем массиве
     * не указано значение первичного ключа.
     */
    public function testDeleteNoPrimaryException()
    {
        $tableName = 'testDelete';
        $columnsNames = [
            'id',
            'row1',
        ];
        $columnsDefinitions = [
            new field\IntNumber,
            new field\Line,
        ];
        $columns = array_combine($columnsNames, $columnsDefinitions);

        $mapper = $this->getMockBuilder(AbstractMapper::class)
            ->setMethods(['getMap', 'getSqlName', 'getSqlPrimary'])
            ->getMock();
        $mapper->method('getMap')->will($this->returnValue($columns));
        $mapper->method('getSqlName')->will($this->returnValue($tableName));
        $mapper->method('getSqlPrimary')->will($this->returnValue([reset($columnsNames)]));

        $mysql = new PdoConnection($this->getPdo());

        $this->expectException(InvalidArgumentException::class, 'id');
        $mysql->delete($mapper, ['row1' => 'row']);
    }

    /**
     * Проверяет, что объект создает таблицу как указано в маппере.
     */
    public function testCreateTable()
    {
        $tableName = 'testCreateTable';
        $columnsNames = [
            'col_' . $this->faker()->unique()->word,
            'col_' . $this->faker()->unique()->word,
            'col_' . $this->faker()->unique()->word,
        ];
        $columnsDefinitions = [
            new field\IntNumber,
            new field\Line,
            new field\Date,
        ];
        $columns = array_combine($columnsNames, $columnsDefinitions);

        $mapper = $this->getMockBuilder(AbstractMapper::class)
            ->setMethods(['getMap', 'getSqlName', 'getSqlPrimary'])
            ->getMock();
        $mapper->method('getMap')->will($this->returnValue($columns));
        $mapper->method('getSqlName')->will($this->returnValue($tableName));
        $mapper->method('getSqlPrimary')->will($this->returnValue([reset($columnsNames)]));

        $mysql = new PdoConnection($this->getPdo());
        $mysql->createTable($mapper);

        $this->assertTableExists($tableName);
        $this->assertTableColExists($tableName, $columnsNames[0], 'int');
        $this->assertTableColExists($tableName, $columnsNames[1], 'varchar');
        $this->assertTableColExists($tableName, $columnsNames[2], 'date');
    }

    /**
     * Проверяет, что объект удаляет таблицу, указанную в маппере.
     */
    public function testDropTable()
    {
        $tableName = 'testDropTable';

        $mapper = $this->getMockBuilder(SqlMapperInterface::class)
            ->getMock();
        $mapper->method('getSqlName')->will($this->returnValue($tableName));

        $mysql = new PdoConnection($this->getPdo());

        $this->assertTableExists($tableName);
        $mysql->dropTable($mapper);
        $this->assertTableNotExists($tableName);
    }

    /**
     * Проверяет, что объект удаляет се содержимое таблицы, указанной в маппере.
     */
    public function testTruncateTable()
    {
        $tableName = 'testTruncateTable';

        $mapper = $this->getMockBuilder(SqlMapperInterface::class)
            ->getMock();
        $mapper->method('getSqlName')->will($this->returnValue($tableName));

        $mysql = new PdoConnection($this->getPdo());
        $mysql->truncateTable($mapper);

        $queryTable = $this->getConnection()->createQueryTable(
            $tableName,
            'SELECT * FROM ' . $tableName
        );
        $expectedTable = $this->createXmlDataSet(__DIR__ . '/_fixture/testTruncateTable_expected.xml')
            ->getTable($tableName);

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    /**
     * Проверяет, что объект выбросит исключение, если не удастся подготовить
     * выражение.
     */
    public function testGetStatementException()
    {
        $mapper = $this->getMockBuilder(SqlMapperInterface::class)
            ->getMock();
        $mapper->method('getSqlName')->will($this->returnValue('testTable'));

        $pdo = $this->getMockBuilder(PDO::class)
            ->setMethods(['prepare', 'getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
        $pdo->method('prepare')->will($this->returnValue(false));
        $pdo->method('getAttribute')->will($this->returnValue('sqlite'));

        $mysql = new PdoConnection($pdo);

        $this->expectException(Exception::class, 'testTable');
        $mysql->dropTable($mapper);
    }

    /**
     * Проверяет, что объект перехватит исключение от PDO.
     */
    public function testPDOException()
    {
        $mapper = $this->getMockBuilder(SqlMapperInterface::class)
            ->getMock();
        $mapper->method('getSqlName')->will($this->returnValue('testTable'));

        $pdo = $this->getMockBuilder(PDO::class)
            ->setMethods(['prepare', 'getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
        $pdo->method('prepare')->will($this->throwException(new PDOException));
        $pdo->method('getAttribute')->will($this->returnValue('sqlite'));

        $mysql = new PdoConnection($pdo);

        $this->expectException(Exception::class);
        $mysql->dropTable($mapper);
    }

    /**
     * Проверяет, что объект выбросит исключение, если запрос не прошел.
     */
    public function testErrorInExecPDOException()
    {
        $error = $this->faker()->unique()->word;

        $mapper = $this->getMockBuilder(SqlMapperInterface::class)
            ->getMock();
        $mapper->method('getSqlName')->will($this->returnValue('testTable'));

        $statement = $this->getMockBuilder(PDOStatement::class)
            ->setMethods(['execute', 'errorInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $statement->method('execute')->will($this->returnValue(false));
        $statement->method('errorInfo')->will($this->returnValue([
            'something',
            'something',
            $error,
        ]));

        $pdo = $this->getMockBuilder(PDO::class)
            ->setMethods(['prepare', 'getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
        $pdo->method('prepare')->will($this->returnValue($statement));
        $pdo->method('getAttribute')->will($this->returnValue('sqlite'));

        $mysql = new PdoConnection($pdo);

        $this->expectException(Exception::class, $error);
        $mysql->dropTable($mapper);
    }

    /**
     * @return \PHPUnit\DbUnit\DataSet\IDataSet
     */
    public function getDataSet()
    {
        $compositeDs = new CompositeDataSet;

        $compositeDs->addDataSet(
            $this->createXmlDataSet(__DIR__ . '/_fixture/testSelectRow.xml')
        );
        $compositeDs->addDataSet(
            $this->createXmlDataSet(__DIR__ . '/_fixture/testInsert.xml')
        );
        $compositeDs->addDataSet(
            $this->createXmlDataSet(__DIR__ . '/_fixture/testUpdate.xml')
        );
        $compositeDs->addDataSet(
            $this->createXmlDataSet(__DIR__ . '/_fixture/testDelete.xml')
        );
        $compositeDs->addDataSet(
            $this->createXmlDataSet(__DIR__ . '/_fixture/testTruncateTable.xml')
        );

        return $compositeDs;
    }

    /**
     * Перед тестом накатываем структуру базы данных для тестов.
     */
    public function setUp()
    {
        $pdo = $this->getPdo();

        $pdo->exec('CREATE TABLE testSelectRow (
            id int(11) not null,
            row1 varchar(30),
            row2 varchar(30),
            PRIMARY KEY(id)
        )');
        $pdo->exec('CREATE TABLE testInsert (
            id int(11) not null,
            row1 varchar(30),
            row2 varchar(30),
            PRIMARY KEY(id)
        )');
        $pdo->exec('CREATE TABLE testUpdate (
            id int(11) not null,
            row1 varchar(30),
            row2 varchar(30),
            PRIMARY KEY(id)
        )');
        $pdo->exec('CREATE TABLE testDelete (
            id int(11) not null,
            row1 varchar(30),
            PRIMARY KEY(id)
        )');
        $pdo->exec('CREATE TABLE testDropTable (
            id int(11) not null,
            row1 varchar(30),
            PRIMARY KEY(id)
        )');
        $pdo->exec('CREATE TABLE testTruncateTable (
            id int(11) not null,
            row1 varchar(30),
            row2 varchar(30),
            PRIMARY KEY(id)
        )');

        return parent::setUp();
    }

    /**
     * После теста удаляем всю структуру, которая была создана во время теста.
     */
    public function tearDown()
    {
        $this->getPdo()->exec('DROP TABLE IF EXISTS testSelectRow');
        $this->getPdo()->exec('DROP TABLE IF EXISTS testInsert');
        $this->getPdo()->exec('DROP TABLE IF EXISTS testUpdate');
        $this->getPdo()->exec('DROP TABLE IF EXISTS testDelete');
        $this->getPdo()->exec('DROP TABLE IF EXISTS testCreateTable');
        $this->getPdo()->exec('DROP TABLE IF EXISTS testDropTable');
        $this->getPdo()->exec('DROP TABLE IF EXISTS testTruncateTable');

        return parent::tearDown();
    }
}
