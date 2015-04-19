<?php

namespace Lazer\Classes;

class DatabaseTest extends \PHPUnit_Framework_TestCase {

    use \vfsHelper\Config;

    /**
     * @var Database
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->setUpFilesystem();
        $this->object = new Database;
    }

    /**
     * @covers Lazer\Classes\Database::create
     */
    public function testCreateTable()
    {
        $this->assertFalse($this->root->hasChild('newTable.data.json'));
        $this->assertFalse($this->root->hasChild('newTable.config.json'));
        $this->object->create('newTable', array(
            'myInteger' => 'integer',
            'myString'  => 'string',
            'myBool'    => 'boolean'
        ));
        $this->assertTrue($this->root->hasChild('newTable.data.json'));
        $this->assertTrue($this->root->hasChild('newTable.config.json'));
    }

    /**
     * @covers Lazer\Classes\Database::create
     * @expectedException Lazer\Classes\LazerException
     * @expectedExceptionMessageRegExp #Table ".*" already exists#
     */
    public function testCreateExistingTable()
    {
        $this->object->create('users', array(
            'myInteger' => 'integer',
            'myString'  => 'string',
            'myBool'    => 'boolean'
        ));
    }

    /**
     * @covers Lazer\Classes\Database::create
     */
    public function testRemoveExistingTable()
    {
        $this->assertTrue($this->root->hasChild('users.data.json'));
        $this->assertTrue($this->root->hasChild('users.config.json'));
        $this->object->remove('users');
        $this->assertFalse($this->root->hasChild('users.data.json'));
        $this->assertFalse($this->root->hasChild('users.config.json'));
    }

    /**
     * @covers Lazer\Classes\Database::create
     * @expectedException Lazer\Classes\LazerException
     * @expectedExceptionMessageRegExp #.* File does not exists#
     */
    public function testRemoveNotExistingTable()
    {
        $this->object->remove('someTable');
    }

    /**
     * @covers Lazer\Classes\Database::table
     * @expectedException Lazer\Classes\LazerException
     * @expectedExceptionMessageRegExp #Table "[a-zA-Z0-9_-]+" does not exists#
     */
    public function testTableNotExists()
    {
        $this->object->table('notExistingTable');
    }

    /**
     * @covers Lazer\Classes\Database::table
     */
    public function testTableExists()
    {
        $table = $this->object->table('users');
        $this->assertInstanceOf('Lazer\Classes\Database', $table);
        $this->assertEquals('users', $table->name());
        return $this->object->table('users');
    }

    /**
     * @depends testTableExists
     */
    public function testFindAll($table)
    {
        $results = $table->findAll();
        $this->assertInstanceOf('Lazer\Classes\Database', $results);
        $this->assertSame(4, count($results));
    }

    /**
     * @depends testTableExists
     */
    public function testAsArray($table)
    {
        $results = $table->findAll()->asArray();
        $this->assertInternalType('array', $results);
        $this->assertArrayHasKey(0, $results);

        $resultsKeyField = $table->findAll()->asArray('id');
        $this->assertInternalType('array', $resultsKeyField);
        $this->assertArrayHasKey(3, $resultsKeyField);
        $this->assertArrayNotHasKey(0, $resultsKeyField);

        $resultsValueField = $table->findAll()->asArray(null, 'id');
        $this->assertInternalType('array', $resultsValueField);
        $this->assertArrayNotHasKey(4, $resultsValueField);
        $this->assertArrayHasKey(0, $resultsValueField);

        $resultsKeyValue = $table->findAll()->asArray('id', 'name');
        $this->assertInternalType('array', $resultsValueField);
        $this->assertArraySubset([2 => 'Kriss'], $resultsKeyValue);
    }

    /**
     * @depends testTableExists
     */
    public function testIsCountable($table)
    {
        $results = $table->findAll();
        $this->assertSame(4, count($results));
        $this->assertSame(4, $results->count());
    }

    /**
     * @covers Lazer\Classes\Database::limit
     * @depends testTableExists
     */
    public function testLimit($table)
    {
        $results = $table->limit(1)->findAll();
        $this->assertInstanceOf('Lazer\Classes\Database', $results);
        $this->assertSame(1, count($results));
    }

    /**
     * @covers Lazer\Classes\Database::orderBy
     */
    public function testOrderBy()
    {
        $table   = $this->object->table('order');
        $query   = array();
        $query[] = $table->orderBy('id')->findAll()->asArray();
        $query[] = $table->orderBy('id', 'DESC')->findAll()->asArray();
        $query[] = $table->orderBy('name')->findAll()->asArray();
        $query[] = $table->orderBy('name', 'DESC')->findAll()->asArray();
        $query[] = $table->orderBy('category')->orderBy('name')->findAll()->asArray();
        $query[] = $table->orderBy('category')->orderBy('name', 'DESC')->findAll()->asArray();
        $query[] = $table->orderBy('category')->orderBy('name')->orderBy('number')->findAll()->asArray();

        $this->assertSame(1, reset($query[0])['id']);
        $this->assertSame(9, end($query[0])['id']);

        $this->assertSame(9, reset($query[1])['id']);
        $this->assertSame(1, end($query[1])['id']);

        $this->assertSame(6, reset($query[2])['id']);
        $this->assertSame(4, end($query[2])['id']);

        $this->assertSame(4, reset($query[3])['id']);
        $this->assertSame(6, end($query[3])['id']);

        $this->assertSame(1, reset($query[4])['id']);
        $this->assertSame(7, end($query[4])['id']);

        $this->assertSame(4, reset($query[5])['id']);
        $this->assertSame(6, end($query[5])['id']);

        $this->assertSame(9, reset($query[6])['id']);
        $this->assertSame(7, end($query[6])['id']);
    }

    /**
     * @covers Lazer\Classes\Database::where
     */
    public function testWhere()
    {
        $table   = $this->object->table('users');
        $query   = array();
        $query[] = $table->where('id', '=', 1)->findAll();
        $query[] = $table->where('id', '!=', 4)->findAll();
        $query[] = $table->where('name', '=', 'Kriss')->findAll();
        $query[] = $table->where('name', '!=', 'Kriss')->findAll();
        $query[] = $table->where('id', '>', 2)->findAll();
        $query[] = $table->where('id', '<', 3)->findAll();
        $query[] = $table->where('id', '>=', 2)->findAll();
        $query[] = $table->where('id', '<=', 3)->findAll();
        $query[] = $table->where('id', '<=', 3)->where('id', '>', 1)->findAll();

        foreach ($query[0] as $row)
        {
            $this->assertEquals(1, $row->id);
        }

        foreach ($query[1] as $row)
        {
            $this->assertNotEquals(4, $row->id);
        }

        foreach ($query[2] as $row)
        {
            $this->assertEquals('Kriss', $row->name);
        }

        foreach ($query[3] as $row)
        {
            $this->assertNotEquals('Kriss', $row->name);
        }

        foreach ($query[4] as $row)
        {
            $this->greaterThan(2, $row->id);
        }

        foreach ($query[5] as $row)
        {
            $this->lessThan(3, $row->id);
        }

        foreach ($query[6] as $row)
        {
            $this->greaterThanOrEqual(2, $row->id);
        }

        foreach ($query[7] as $row)
        {
            $this->lessThanOrEqual(3, $row->id);
        }

    }

}
