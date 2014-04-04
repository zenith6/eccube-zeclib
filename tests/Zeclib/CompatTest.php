<?php

class Zeclib_CompatTest extends PHPUnit_Framework_TestCase
{
    public function provideValidValues()
    {
        return array(
            array('null', null),
            array(1, 1),
            array('"string"', 'string'),
            array('[]', array()),
            array('{}', (object)array()),
        );
    }

    /**
     * @dataProvider provideValidValues
     */
    public function testEncode($json, $native)
    {
        $expected = $json;
        $actual = Zeclib_Compat::encodeJson($native);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider provideValidValues
     */
    public function testDecode($json, $native)
    {
        $expected = $native;
        $actual = Zeclib_Compat::decodeJson($json);
        $this->assertEquals($expected, $actual);
    }

    public function testDecodeToArray()
    {
        $expected = array('a' => 1);
        $actual = Zeclib_Compat::decodeJson('{"a":1}', true);
        $this->assertEquals($expected, $actual);
    }

    public function testDecodeToObject()
    {
        $expected = (object)array('a' => 1);
        $actual = Zeclib_Compat::decodeJson('{"a":1}', false);
        $this->assertEquals($expected, $actual);
    }
}
