<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 15-12-2014
 * Time: 02:53
 */

namespace Tivie\Command;

use Tivie\Command\Exception\InvalidArgumentException;

class Argument
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    public $values = array();

    /**
     * @var int|null
     */
    private $os;

    /**
     * @var bool|null
     */
    private $escape = null;

    /**
     * Create a new Argument Object
     *
     * @param  string                   $key
     * @param  string|array|null        $value  [optional]
     * @param  int                      $os     [optional]
     * @param  bool                     $escape [optional]
     * @throws InvalidArgumentException
     */
    public function __construct($key, $value = null, $os = null, $escape = null)
    {
        $this->escape = (!is_null($escape)) ? !!$escape : null;
        $this->setKey($key);

        if ($os !== null) {
            if (!is_int($os)) {
                throw new InvalidArgumentException('integer or null', 3);
            }
        }
        $this->os = $os;

        if (!is_array($value)) {
            $value = array($value);
        }

        $this->setValues($value);
    }

    /**
     * Get the argument Key
     *
     * @param  bool   $withPrefix [optional] Default is false
     * @return string
     */
    public function getKey($withPrefix = false)
    {
        $key = ($withPrefix) ? $this->prefix : '';
        $key .= $this->key;

        return $key;
    }

    /**
     * Set the Argument key
     *
     * @param  string                   $key
     * @return string
     * @throws InvalidArgumentException
     */
    public function setKey($key)
    {
        $arr = $this->parseKey($key);
        $this->key = $arr['key'];
        $this->identifier = $arr['identifier'];
        $this->prefix = $arr['prefix'];

        return $this;
    }

    /**
     * Get the prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the prefix
     *
     * @param  string $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        $this->identifier = $this->prefix.$this->key;

        return $this;
    }

    /**
     * Get the identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get the argument's values
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Set the argument values
     *
     * @param  array $values Array of strings or empty array
     * @return $this
     */
    public function setValues(array $values)
    {
        $this->values = array();

        foreach ($values as $value) {
            if (!is_null($value)) {
                $this->addValue($value);
            }
        }

        return $this;
    }

    /**
     * Replace an existing value identified by it's index in the values array
     *
     * @param  int    $index
     * @param  string $newValue
     * @return $this
     */
    public function replaceValue($index, $newValue)
    {
        $this->values[$index] = (string) $newValue;

        return $this;
    }

    /**
     * Add a value to the argument's values array
     *
     * @param  string $value
     * @return $this
     */
    public function addValue($value)
    {
        $this->values[] = (string) $value;

        return $this;
    }

    /**
     * Get the OS this argument is specific for
     *
     * @return int|null An integer representing the OS or OS Family (constant specified in Tivie/OS library) or null if
     *                  the Argument is not specific to any OS or OS Family.
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * Set the OS this argument is specific for
     *
     * @param int|null $os Should use the OS or OS Family constants specified by Tivie/OS library. Passing null means
     *                     the argument is not specific to any OS or OS Family.
     */
    public function setOs($os)
    {
        $this->os = $os;
    }

    /**
     * Check if this argument will be escaped
     *
     * @return bool|null If true, the argument will be escaped. If false, the argument won't be escaped. If null, escape
     *                   flag isn't set and the escape flag won't be enforced.
     */
    public function willEscape()
    {
        return $this->escape;
    }

    /**
     * Set if this argument should be escaped
     *
     * @param  bool|null $bool [optional] Default is true. If true, the argument will be escaped. If false, the argument
     *                         won't be escaped. If null, the escape flag won't be set and escaping won't be enforced.
     * @return $this
     */
    public function escape($bool = true)
    {
        $this->escape = !!$bool;

        return $this;
    }

    /**
     * Private Method: Parses the key a retrieves the prefix, key and identifier.
     *
     * @param $key
     * @return array
     * @throws InvalidArgumentException
     */
    private function parseKey($key)
    {
        if ($key == null) {
            throw new InvalidArgumentException('string', 0, 'Cannot be null');
        }

        if (!is_string($key)) {
            throw new InvalidArgumentException('string', 0);
        }

        $arr = array(
            'prefix' => null,
        );

        $arr['identifier'] = $key;

        $pattern = '#^--|^-|^/#';
        if (preg_match($pattern, $key, $matches)) {
            $arr['prefix'] = $matches[0];
        }
        $arr['key'] = preg_replace($pattern, '', $key);

        return $arr;
    }
}
