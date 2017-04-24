<?php

namespace App\Config;

/**
 * Class which takes care of the caching of configuration files
 */
class Cacher
{
    /**
     * The root configuration
     * @var App\Config\Config
     */
    protected $config;

    /**
     * Flag to determine if the configuration is loaded from cache
     * @var bool
     */
    protected $isCached = false;

    /**
     * The filename of the confguration cache
     * @var string
     */
    protected $cacheFile;

    /**
     * The 'global' cacher index
     * @var static
     */
    protected static $instance;

    /**
     * Constructs a chacer with a spefied cache file
     *
     * @param string $cacheFile The file name of the cache file
     * @param bool $forceReload Set to TRUE to ignore the cache and always build the configuration through {@see build()}
     */
    public function __construct($cacheFile, $forceReload = false)
    {
        $this->cacheFile = $cacheFile;
        if (!$forceReload) {
            $this->loadCache();
        } else {
            $this->config = new Config();
        }
    }

    /**
     * Loads configuration settings into the root configuration
     *
     * @param string|array $fileOrArray Either a file name of a PHP file to include or an array with configuration settings
     * @return static $this for chaining
     */
    public function loadConfig($fileOrArray)
    {
        if (is_array($fileOrArray)) {
            $this->config->inject($fileOrArray);
        } else {
            $this->config->inject(include $fileOrArray);
        }
        return $this;
    }

    /**
     * Stores the root configuration in a cache file, only if the configuration wasn't loaded from the cache.
     *
     * @return static $this for chaining
     */
    public function storeCache()
    {
        if (!$this->isCached) {
            $php = "<?php" . PHP_EOL . PHP_EOL . "return " . $this->config->serializeAsPHP() . ';' . PHP_EOL;
            file_put_contents($this->cacheFile, $php);
        }
        return $this;
    }

    /**
     * Attempts to load the root configuration from cache
     *
     * @return static $this for chaining
     */
    public function loadCache()
    {
        if (file_exists($this->cacheFile)) {
            $configArray = include $this->cacheFile;
            $this->config = new Config($configArray);
            $this->isCached = true;
        } else {
            $this->config = new Config();
        }
        return $this;
    }

    /**
     * Calls the build function to build the configuration, only if the configuration couldn't be loaded from cache
     *
     * @return static $this for chaining
     */
    public function build(callable $buildFunc)
    {
        if (!$this->isCached) {
            $buildFunc($this);
        }

        return $this;
    }

    /**
     * Convenience method to create a Cacher object. If an instance was already created, the method will return that instance (ignoring the $cacheFile parameter)
     *
     * @param string $cacheFile The file name of the cache file. If no new instance is created, this parameter will be ignored
     * @param bool $forceReload Set to TRUE to ignore the cache and always build the configuration through {@see build()}
     * @return static The Cacher object
     */
    public static function factory($cacheFile, $forceReload = false)
    {
        if (!self::$instance) {
            self::$instance = new Cacher($cacheFile, $forceReload);
        }
        return self::$instance;
    }

    /**
     * Gets the root configuration object
     *
     * @return Config The configuration object
     */
    public function getConfig()
    {
        return $this->config;
    }
}
