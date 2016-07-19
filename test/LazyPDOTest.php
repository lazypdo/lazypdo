<?php

/**
 * This file is part of LazyPDO.
 *
 * (c) Alexey Karapetov <karapetov@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LazyPDO;

use PDO;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use RuntimeException;

class LazyPDOTest extends PHPUnit_Framework_TestCase
{
    private $pdo;

    private $lazy;

    protected function setUp()
    {
        $this->pdo = $this->getMockBuilder('stdClass')
            ->setMethods(array(
                'setAttribute',
                'inTransaction',
                'beginTransaction',
                'getAttribute',
                'commit',
                'rollBack',
                'errorCode',
                'errorInfo',
                'exec',
                'prepare',
                'quote',
                'query',
                'lastInsertId',
            ))
            ->getMock();
        $this->lazy = $this->getMockBuilder('LazyPDO\\LazyPDO')
            ->setMethods(array('getPDO'))
            ->setConstructorArgs(array('dsn', 'user', 'pass', array('key' => 'val')))
            ->getMock();
        $this->lazy->expects($this->any())
            ->method('getPDO')
            ->will($this->returnValue($this->pdo));
    }

    public function booleanValuesProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    public function intValuesProvider()
    {
        return array(
            array(0),
            array(-42),
            array(42),
        );
    }

    public function nullValuesProvider()
    {
        return array(
            array(null, null),
        );
    }

    public function intOrNullValuesProvider()
    {
        return array_merge(
            $this->intValuesProvider(),
            $this->nullValuesProvider()
        );
    }

    public function testSetAttributeShouldBePassedToRealPDOAndGatheredInOptionsIfOk()
    {
        $this->pdo->expects($this->once())
            ->method('setAttribute')
            ->with('my_attr', 'my_value')
            ->will($this->returnValue(true));
        $this->assertTrue($this->lazy->setAttribute('my_attr', 'my_value'));
        $props = unserialize($this->lazy->serialize());
        $this->assertEquals('my_value', $props[3]['my_attr']);
    }

    public function testSetAttributeShouldNotBeGatheredOnFail()
    {
        $this->pdo->expects($this->once())
            ->method('setAttribute')
            ->with('my_attr', 'my_value')
            ->will($this->returnValue(false));
        $this->assertFalse($this->lazy->setAttribute('my_attr', 'my_value'));
        $props = unserialize($this->lazy->serialize());
        $this->assertArrayNotHasKey('my_attr', $props[3]);
    }

    public function testGetPDO()
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite not loaded');
        }
        $class = new ReflectionClass('LazyPDO\\LazyPDO');
        $method = $class->getMethod('getPDO');
        $method->setAccessible(true);

        $dsn = 'sqlite::memory:';
        $lazy = new LazyPDO($dsn, 'user', 'pass', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $pdo = $method->invoke($lazy);
        $this->assertInstanceOf('PDO', $pdo);
        $this->assertThat($pdo, $this->identicalTo($method->invoke($lazy)));
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Can not serialize in transaction
     */
    public function testSerializeShouldThrowExceptionInTransaction()
    {
        $lazy = $this->getMockBuilder('LazyPDO\\LazyPDO')
            ->setConstructorArgs(array('dsn', 'user', 'pass', array()))
            ->setMethods(array('inTransaction'))
            ->getMock();
        $lazy->expects($this->once())
            ->method('inTransaction')
            ->will($this->returnValue(true));
        $lazy->serialize();
    }

    public function testSerialize()
    {
        $dsn = 'sqlite::memory:';
        $lazy = new LazyPDO($dsn, 'user', 'pass');
        $serialized = serialize($lazy);
        $this->assertEquals('C:15:"LazyPDO\\LazyPDO":73:{a:4:{i:0;s:' . mb_strlen($dsn) . ':"' . $dsn . '";i:1;s:4:"user";i:2;s:4:"pass";i:3;a:0:{}}}', $serialized);
        $this->assertEquals($lazy, unserialize($serialized));
    }

    public function testSerializationShouldPreserveAttributes()
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite not loaded');
        }
        $dsn = 'sqlite::memory:';
        $lazy = new LazyPDO($dsn, 'user', 'pass', array());
        $this->assertNotEquals(PDO::ERRMODE_EXCEPTION, $lazy->getAttribute(PDO::ATTR_ERRMODE));
        $lazy->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $lazy->getAttribute(PDO::ATTR_ERRMODE));
        $lazy = unserialize(serialize($lazy));
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $lazy->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testTransactions()
    {
        $dsn = 'sqlite::memory:';
        $lazy = new LazyPDO($dsn, 'user', 'pass', array());
        $lazy->exec('CREATE TABLE my_test (id INT PRIMARY KEY)');

        // Successful case
        $lazy->beginTransaction();
        $insert = $lazy->prepare('INSERT INTO my_test VALUES (:id)');
        $insert->execute(array(
            ':id' => 1,
        ));
        $lazy->commit();

        // Unsuccessful case
        $lazy->beginTransaction();
        $insert = $lazy->prepare('INSERT INTO my_test VALUES (:id)');
        $insert->execute(array(
            ':id' => 2,
        ));
        $lazy->rollBack();

        $select = $lazy->prepare('SELECT * FROM my_test');
        $select->execute();
        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                )
            ),
            $select->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}
