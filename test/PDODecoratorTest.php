<?php

namespace LazyPDO\Test;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PDODecoratorTest extends TestCase
{
    protected MockObject $pdoDecorator;

    protected MockObject $pdoStub;

    protected function setUp()
    {
        $this->pdoStub = $this->getMockBuilder('stdClass')
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

        $this->pdoDecorator = $this->getMockForAbstractClass('LazyPDO\\PDODecorator');

        $this->pdoDecorator->expects($this->any())
            ->method('getPDO')
            ->will($this->returnValue($this->pdoStub));
    }

    public function boolValuesProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @dataProvider boolValuesProvider
     */
    public function testSetAttribute($returnFlag)
    {
        $this->pdoStub->expects($this->once())
            ->method('setAttribute')
            ->with('testAttribute', 'testValue')
            ->will($this->returnValue($returnFlag));

        $this->assertThat(
            $this->pdoDecorator->setAttribute('testAttribute', 'testValue'),
            $this->identicalTo($returnFlag)
        );
    }

    public function testGetAttribute()
    {
        $this->pdoStub->expects($this->once())
            ->method('getAttribute')
            ->with('testAttribute')
            ->will($this->returnValue('testValue'));

        $this->assertThat(
            $this->pdoDecorator->getAttribute('testAttribute'),
            $this->identicalTo('testValue')
        );
    }

    /**
     * @dataProvider boolValuesProvider
     * @param $returnFlag
     */
    public function testPrepare($returnFlag)
    {
        $this->pdoStub->expects($this->once())
            ->method('prepare')
            ->with('statement', array('option' => 'value'))
            ->will($this->returnValue($returnFlag));

        $this->assertThat(
            $this->pdoDecorator->prepare('statement', array('option' => 'value')),
            $this->identicalTo($returnFlag)
        );
    }

    /**
     * @dataProvider boolValuesProvider
     * @param $result
     */
    public function testBeginTransaction($result)
    {
        $this->pdoStub->expects($this->once())
            ->method('beginTransaction')
            ->will($this->returnValue($result));

        $this->assertThat(
            $this->pdoDecorator->beginTransaction(),
            $this->identicalTo($result)
        );
    }

    /**
     * @dataProvider boolValuesProvider
     * @param $result
     */
    public function testInTransaction($result)
    {
        $this->pdoStub->expects($this->once())
            ->method('inTransaction')
            ->will($this->returnValue($result));

        $this->assertThat(
            $this->pdoDecorator->inTransaction(),
            $this->identicalTo($result)
        );
    }

    /**
     * @dataProvider boolValuesProvider
     * @param $result
     */
    public function testRollBack($result)
    {
        $this->pdoStub->expects($this->once())
            ->method('rollBack')
            ->will($this->returnValue($result));

        $this->assertThat(
            $this->pdoDecorator->rollBack(),
            $this->identicalTo($result)
        );
    }

    /**
     * @dataProvider boolValuesProvider
     */
    public function testCommit($result)
    {
        $this->pdoStub->expects($this->once())
            ->method('commit')
            ->will($this->returnValue($result));

        $this->assertThat(
            $this->pdoDecorator->commit(),
            $this->identicalTo($result)
        );
    }

    public function testErrorCode()
    {
        $this->pdoStub->expects($this->once())
            ->method('errorCode')
            ->will($this->returnValue('testErrorCode'));

        $this->assertThat(
            $this->pdoDecorator->errorCode(),
            $this->identicalTo('testErrorCode')
        );
    }

    public function testErrorInfo()
    {
        $this->pdoStub->expects($this->once())
            ->method('errorInfo')
            ->will($this->returnValue(array('testErrorInfo')));

        $this->assertThat(
            $this->pdoDecorator->errorInfo(),
            $this->identicalTo(array('testErrorInfo'))
        );
    }

    public function testExec()
    {
        $testValue = 1337;
        $this->pdoStub->expects($this->once())
            ->method('exec')
            ->with('testStatement')
            ->will($this->returnValue($testValue));

        $this->assertThat(
            $this->pdoDecorator->exec('testStatement'),
            $this->identicalTo($testValue)
        );
    }

    public function testQuote()
    {
        $testParameter = 'testParameter';
        $testType = 'testType';
        $this->pdoStub->expects($this->once())
            ->method('quote')
            ->with($testParameter, $testType)
            ->willReturn('quotedParameter');

        $this->assertEquals('quotedParameter', $this->pdoDecorator->quote($testParameter, $testType));
    }

    public function testQuoteWithUnspecifiedType()
    {
        $testParameter = 'testParameter';
        $this->pdoStub->expects($this->once())
            ->method('quote')
            ->willReturn('quotedParameter');

        $this->assertEquals('quotedParameter', $this->pdoDecorator->quote($testParameter));
    }

    public function testLastInsertIdWithUnspecifiedName()
    {
        $testResult = 'testResult';
        $this->pdoStub->expects($this->once())
            ->method('lastInsertId')
            ->with(null)
            ->will($this->returnValue($testResult));

        $this->assertThat($this->pdoDecorator->lastInsertId(), $this->identicalTo($testResult));
    }

    public function testQuery()
    {
        $result = (object) array('hello' => 'world');
        $query = 'my query';
        $this->pdoStub->expects($this->once())
            ->method('query')
            ->with($query)
            ->will($this->returnValue($result));
        $this->assertThat($this->pdoDecorator->query($query), $this->identicalTo($result));
    }

    public function testQueryOverloaded()
    {
        $result = (object) array('hello' => 'world');
        $query = 'my query';
        $this->pdoStub->expects($this->once())
            ->method('query')
            ->with($query, 'test', 'overloaded', 'call')
            ->will($this->returnValue($result));
        $this->assertThat(
            $this->pdoDecorator->query($query, 'test', 'overloaded', 'call'),
            $this->identicalTo($result)
        );
    }
}
