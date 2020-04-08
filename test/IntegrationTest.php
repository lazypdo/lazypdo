<?php

namespace LazyPDO\Test;

use LazyPDO\LazyPDO;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private $db;

    public function setUp()
    {
        $this->db = tempnam(sys_get_temp_dir(), 'php');
    }

    public function tearDown()
    {
        unlink($this->db);
    }

    public function testPdoSurvivesSerialization()
    {
        $this->skipIfNoSqlite();
        $pdo = new LazyPDO("sqlite:{$this->db}", null, null, array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
        $pdo->exec('CREATE TABLE my_test (id INT, name TEXT)');
        $insert = $pdo->prepare('INSERT INTO my_test (id, name) VALUES (:id, :name)');
        $insert->execute(array(
            ':id' => 1,
            ':name' => 'foo',
        ));

        /**
         * @var \PDO $survivor
         */
        $survivor = unserialize(serialize($pdo));
        $select = $survivor->prepare('SELECT name FROM my_test WHERE id = :id');
        $select->execute(array(
            'id' => 1
        ));
        $this->assertEquals('foo', $select->fetchColumn());
    }

    private function skipIfNoSqlite()
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite not loaded');
        }
    }
}
