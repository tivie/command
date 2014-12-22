<?php
/**
 * -- tivie-command --
 * ArgumentTest.php created at 21-12-2014
 *
 * Copyright 2014 Estevão Soares dos Santos
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

namespace Tivie\Command;

use \Tivie\OS\Detector;

class ArgumentTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $detector = new Detector();
    }

    /**
     * @covers \Tivie\Command\Argument::__construct
     */
    public function testConstructor()
    {
        $id = '--foo';
        $key = "foo";
        $value = 'bar';
        $os = \Tivie\OS\LINUX;
        $escape = true;
        $arg = new Argument($id, $value, $os, $escape);

        self::assertEquals($id, $this->getPrivatePropertyValue($arg, 'identifier'),
            "Retrieved identifier does not match expected value");
        self::assertEquals($key, $this->getPrivatePropertyValue($arg, 'key'),
            "Retrieved key does not match expected value");
        self::assertEquals(array($value), $this->getPrivatePropertyValue($arg, 'values'),
            "Retrieved Argument 'values' don't match expected 'values'");
        self::assertEquals($os, $this->getPrivatePropertyValue($arg, 'os'), "Retrieved OS doesn't match passed OS");
        self::assertEquals($escape, $this->getPrivatePropertyValue($arg, 'escape'),
            "Retrieved escape flag doesn't match set flag");
    }

    private function getPrivatePropertyValue($arg, $propName)
    {
        $ref = new \ReflectionClass($arg);
        $prop = $ref->getProperty($propName);
        $prop->setAccessible(true);

        return $prop->getValue($arg);
    }

    /**
     * @covers \Tivie\Command\Argument::getKey
     * @covers \Tivie\Command\Argument::setKey
     * @covers \Tivie\Command\Argument::getPrefix()
     * @covers \Tivie\Command\Argument::setPrefix()
     * @covers \Tivie\Command\Argument::getIdentifier()
     * @covers \Tivie\Command\Argument::setIdentifier()
     * @covers \Tivie\Command\Argument::getValues()
     * @covers \Tivie\Command\Argument::setValues()
     * @covers \Tivie\Command\Argument::replaceValue()
     * @covers \Tivie\Command\Argument::addValue()
     * @covers \Tivie\Command\Argument::getOs()
     * @covers \Tivie\Command\Argument::setOs()
     * @covers \Tivie\Command\Argument::willEscape()
     * @covers \Tivie\Command\Argument::escape
     */
    public function testSettersAndGetters()
    {
        $id = '--foo';
        $key = "foo";
        $value = 'bar';
        $value2 = 'baz';
        $os = \Tivie\OS\LINUX;
        $escape = true;
        $arg = new Argument('bla');

        //setKey() / getKey()
        $arg->setKey($id);
        self::assertEquals(
            $id,
            $this->getPrivatePropertyValue($arg, 'identifier'),
            "Property 'identifier' doesn't have the expected value when using setKey() method"
        );
        self::assertEquals(
            $id,
            $arg->getIdentifier(),
            "getIdentifier() method doesn't return the proper value"
        );
        self::assertEquals(
            $key,
            $this->getPrivatePropertyValue($arg, 'key'),
            "Property 'key' doesn't have the expected value when using setKey() method"
        );
        self::assertEquals(
            $key,
            $arg->getKey(),
            "getKey() method doesn't return the proper value"
        );
        self::assertEquals(
            $id,
            $arg->getKey(true),
            "getKey() method doesn't return the proper value passing \$withPrefix as true"
        );

        //addValue() / getValue()
        $arg->addValue($value);
        self::assertEquals(
            array($value),
            $this->getPrivatePropertyValue($arg, 'values'),
            "Property 'value' isn't being set correctly when using addValue() method"
        );
        self::assertEquals(
            array($value),
            $arg->getValues(),
            "getValues() method doesn't return the proper value"
        );

        // setValues()
        $values = array($value, $value2);
        $arg->setValues($values);
        self::assertEquals(
            $values,
            $this->getPrivatePropertyValue($arg, 'values'),
            "Property 'value' isn't being set correctly when using setValues() method"
        );
        self::assertEquals(
            $values,
            $arg->getValues(),
            "getValues() method doesn't return the proper values when those are set with setValues() method"
        );

        // ReplaceValues()
        $nValue = 'bazinga';
        $values = array($nValue, $value2);
        $arg->replaceValue(0, $nValue);
        self::assertEquals(
            $values,
            $this->getPrivatePropertyValue($arg, 'values'),
            "Property 'value' isn't being set correctly when using replaceValue() method"
        );

        // setOs() / getOs()
        $arg->setOs($os);
        self::assertEquals(
            $os,
            $this->getPrivatePropertyValue($arg, 'os'),
            "Property 'os' doesn't have the expected value when using getOs() method"
        );
        self::assertEquals(
            $os,
            $arg->getOs(),
            "getOs() method doesn't return the proper value"
        );

        // escape() / willEscape()
        $arg->escape($escape);
        self::assertEquals(
            $escape,
            $this->getPrivatePropertyValue($arg, 'escape'),
            "Property 'escape' doesn't have the expected value when using escape() method"
        );
        self::assertEquals(
            $escape,
            $arg->willEscape(),
            "willEscape() method doesn't return the proper bool"
        );
    }
}
