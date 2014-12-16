<?php
/**
 * Created by PhpStorm.
 * User: Estevao
 * Date: 15-12-2014
 * Time: 02:53
 */

namespace Tivie\Command;


use Tivie\Command\Exception\InvalidArgumentException;
use Tivie\Command\OS\OSDetector;
use Tivie\Command\OS\OSDetectorInterface;

class Argument
{
    public $key;

    public $identifier;

    public $values;

    public $os;

    private $escape = true;

    protected $osDetector;

    public function __construct(
        $key,
        $value = null,
        $os = null,
        $escape = true,
        $prepend = null,
        OSDetectorInterface $osDetector = null
    ) {
        $this->osDetector = ($osDetector) ? $osDetector : new OSDetector();

        if ($key == null) {
            throw new InvalidArgumentException('string', 0, 'Cannot be null');
        }

        $this->setKey($key);

        if ($os !== null) {
            if (!is_int($os)) {
                throw new InvalidArgumentException('integer or null', 3);
            }
        }

        $this->os = $os;
        $this->escape = !!$escape;

        $this->identifier = preg_replace('#^--|^-|^/#', '', $key);

        $this->key = $this->prepareKey($key, $escape, $prepend);


        if (!is_array($value)) {
            $value = array($value);
        }

        $this->setValues($value);
    }

    private function escapeKey($key)
    {
        $prepend = '';
        if (preg_match('#^--|^-|^/#', $key, $matches)) {
            $prepend = $matches[0];
            $key = preg_replace('#^--|^-|^/#', '',$key);
        }
        return $prepend . escapeshellarg($key);
    }

    private function prepareKey($key, $escape, $prepend)
    {
        $key = ($escape) ? $this->escapeKey($key) : $key;

        if ($prepend & PREPEND_WINDOWS_STYLE && $prepend & PREPEND_UNIX_STYLE) {
            switch ($this->osDetector->detect()->family) {
                case \Tivie\Command\OS\WINDOWS_FAMILY:
                    $prepend = PREPEND_WINDOWS_STYLE;
                    break;
                case \Tivie\Command\OS\UNIX_FAMILY:
                    $prepend = PREPEND_UNIX_STYLE;
                    break;

                default:
                    $key = preg_replace('#^--|^-|^/#', '',$key);
                    $key = "$prepend$key";
                    break;
            }
        }

        if ($prepend & PREPEND_WINDOWS_STYLE) {
            $key = preg_replace('#^--|^-|^/#', '',$key);
            $key = "/$key";

        } else if ($prepend & PREPEND_UNIX_STYLE) {
            $key = preg_replace('#^--|^-|^/#', '',$key);
            $key = (strlen($key) === 1) ? "-$key" : "--$key";
        }

        return $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $key
     * @return string
     * @throws InvalidArgumentException
     */
    public function setKey($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('string', 0);
        }
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function setValues(array $values)
    {
        $this->values = array();

        foreach ($values as $value) {
            $this->addValue($value);
        }

        return $this;
    }

    /**
     * @param $index
     * @param $newValue
     * @return $this
     */
    public function replaceValue($index, $newValue)
    {
        $this->values[$index] = $newValue;

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function addValue($value)
    {
        $this->values[] = $this->parseValue($value);

        return $this;
    }

    private function parseValue($value)
    {
        if (!is_null($value)) {
            $pat = '/^".*"$/';
            if (preg_match($pat, $value)) {
                $value = preg_replace($pat, '', $value);
            }
            $value = ($this->escape) ? escapeshellarg($value) : $value;
        }
        return $value;
    }

    /**
     * @return int|null
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param int|null $os
     */
    public function setOs($os)
    {
        $this->os = $os;
    }
}