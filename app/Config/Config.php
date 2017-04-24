<?php

namespace App\Config;

use Iterator;
use ReflectionFunction;

class Config implements Iterator
{
    /**
     * The configuration settings
     * @var array
     */
    protected $config = [];

    /**
     * The iterator keys
     * @var array
     */
    protected $itKeys = [];

    /**
     * The number of iterator keys
     * @var int
     */
    protected $itNumKeys = 0;

    /**
     * The current iterator key index
     * @var array
     */
    protected $it = 0;

    /**
     * Initializes the configuration object with config settings
     *
     * @param array $array The configuration settings
     */
    public function __construct($array = null)
    {
        if ($array) {
            $this->inject($array);
        }
    }

    /**
     * Injects configuration settings into the configuration object. Configuration will be deep-merged
     *
     * @param array $array The configuration settings
     */
    public function inject($array)
    {
        foreach (array_keys($array) as $key) {
            if (isset($this->config[$key]) && ($this->config[$key] instanceof Config)) {
                $this->config[$key]->inject($array[$key]);
            } else {
                $this->config[$key] = is_array($array[$key]) ? new Config($array[$key]) : $array[$key];
            }
        }
    }

    /**
     * Gets a configuration setting by name
     *
     * @param string $name The name of the setting
     * @return mixed The setting value. Returns NULL if the setting doesn't exist
     */
    public function __get($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    /**
     * Sets a configuration setting
     *
     * @param string $name The name of the setting
     * @param mixed $value The setting value
     */
    public function __set($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * Serializes the configuration object as an array in PHP notation, so that
     * it can be placed in a file which can be included via PHP.
     *
     * @param int $indent The base indentation in number of tabs
     * @return string The PHP code
     */
    public function serializeAsPHP($indent = 0)
    {
        $php = "[" . PHP_EOL;
        foreach (array_keys($this->config) as $i => $key) {
            if ($i > 0) {
                $php .= "," . PHP_EOL;
            }
            $php .= str_repeat("    ", $indent) . "    '{$key}' => ";
            // Check by type
            if (is_bool($this->config[$key])) {
                $php .= $this->config[$key] ? "true" : "false";
            }
            else if (is_callable($this->config[$key])) {
                $func = new ReflectionFunction($this->config[$key]);
                $filename = $func->getFileName();
                $startLine = $func->getStartLine()-1;
                $endLine = $func->getEndLine()-1;
                $length = $endLine - $startLine;
                $source = file($filename);

                $body = '';
                $baseTabs = -1;
                for ($i = $startLine; $i <= $endLine; $i++) {
                    $tabs = 0;
                    $line = $source[$i];
                    if (preg_match("/^\t+/", $line, $match)) {
                        $tabs = strlen($match[0]);
                        $line = substr($line, $tabs);
                    } else if (preg_match("/^\s+/", $line, $match)) {
                        $spaces = strlen($match[0]);
                        $tabs = round($spaces / 4);
                        $line = substr($line, $spaces);
                    }
                    if ($baseTabs == -1) {
                        $baseTabs = $tabs;
                    }
                    $body .= str_repeat("    ", 1 + $indent + $tabs - $baseTabs) . $line;
                }

                $body = preg_replace("/[;,\r\n]+$/", '', $body);
                $cut = strpos($body, 'function');

                $php .= substr($body, $cut);
            }
            else if (is_float($this->config[$key]) || is_int($this->config[$key])) {
                $php .= $this->config[$key];
            }
            else if (is_object($this->config[$key])) {
                if (method_exists($this->config[$key], 'serializeAsPHP')) {
                    $php .= $this->config[$key]->serializeAsPHP($indent + 1);
                } else {
                    $php .= "unserialize(base64_decode(" . base64_encode(serialize($this->config[$key])) . "))";
                }
            } else if (is_string($this->config[$key])) {
                $php .= '"' . str_replace('"', '\\"', $this->config[$key]) . '"';
            }
        }
        $php .= PHP_EOL . str_repeat("    ", $indent) . "]";

        return $php;
    }

    /**
     * Returns the array equivalent of the configuration
     *
     * @return array The array
     */
    public function toArray()
    {
        $array = [];
        foreach (array_keys($this->config) as $key) {
            if (is_object($this->config[$key]) && ($this->config[$key] instanceof Config)) {
                $array[$key] = $this->config[$key]->toArray();
            } else {
                $array[$key] = $this->config[$key];
            }
        }

        return $array;
    }

    /**
     * Returns the current setting in the iteration
     *
     * @return mixed The setting
     */
    public function current()
    {
        return $this->config[$this->key()];
    }

    /**
     * Returns the name of the current setting in the iteration
     *
     * @return mixed The setting name
     */
    public function key()
    {
        return $this->itKeys[$this->it];
    }

    /**
     * Advances the iterator to the next key
     */
    public function next()
    {
        $this->it++;
    }

    /**
     * Rewinds and (re-)initializes the iterator
     */
    public function rewind()
    {
        $this->it = 0;
        $this->itKeys = array_keys($this->config);
        $this->itNumKeys = count($this->itKeys);
    }

    /**
     * Checks if the current iteration position is valid (i.e. it points to an actual setting)
     *
     * @return bool The validness of the key
     */
    public function valid()
    {
        return $this->it < $this->itNumKeys;
    }
}