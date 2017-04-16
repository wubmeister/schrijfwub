<?php

namespace App;

class Template
{
    /**
     * The global theme
     * @var string
     */
    public static $theme = 'default';

    /**
     * The themes directory
     * @var string
     */
    public static $themesDir;

    /**
     * The template name
     * @var string
     */
    protected $name;

    /**
     * The specific theme for this template
     * @var string
     */
    protected $overrideTheme = null;

    /**
     * The variables for the template
     * @var array
     */
    protected $variables = [];

    /**
     * Stack op capture names
     * @var array
     */
    protected $captureStack = [];

    /**
     * The globally accessible captures
     * @var array
     */
    protected static $captures = [];

    /**
     * The globally accessible variables
     * @var array
     */
    protected static $globals = [];

    /**
     * Constructs a template with a given name
     *
     * @param string $name The template name
     * @param string $theme Specify a theme name to override the global theme
     */
    public function __construct($name, $theme = null)
    {
        $this->name = $name;
        $this->overrideTheme = $theme;
    }

    /**
     * Overrides the default global theme
     *
     * @param string $theme The theme name. Omit the theme name to reset to the default global theme
     */
    public function setTheme($theme = null)
    {
        $this->overrideTheme = $theme;
    }

    /**
     * Gets the theme for this template
     *
     * @param string $theme The theme name. Omit the theme name to reset to the default global theme
     */
    public function getTheme($theme = null)
    {
        return $this->overrideTheme ? $this->overrideTheme : self::$theme;
    }

    /**
     * Sets a theme variable
     *
     * @param string $name The variable name
     * @param mixed $value The value
     */
    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Renders the template and returns the rendered content
     *
     * @param array $variables Additional variables to pass to the template
     */
    public function render($variables = [])
    {
        $variables = array_merge($this->variables, $variables);
        $fileName = $this->resolveFileName();

        if (!$fileName) {
            throw new \Exception("No template found with the name '{$this->name}'");
        }

        extract($variables);
        ob_start();
        include($fileName);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Find the correct file to use
     *
     * @return string An existing file name or null if no matching file was found
     */
    protected function resolveFileName()
    {
        $theme = $this->getTheme();
        $themePath = self::$themesDir . '/' . $theme . '/' . $this->name . '.phtml';

        if (file_exists($themePath)) {
            return $themePath;
        } else {
            $defaultThemePath = self::$themesDir . '/default/' . $this->name . '.phtml';
            if (file_exists($defaultThemePath)) {
                return $defaultThemePath;
            }
        }

        return null;
    }

    /**
     * Starts capturing a piece of the template to store it as globally accessible variable
     *
     * @param string $name The name to label the captured content
     * @param bool $append Set to TRUE to append the content to previously captured content
     */
    protected function startCapture($name, $append = false)
    {
        $this->captureStack[] = [ $name, $append ];
        ob_start();
    }

    /**
     * Ends capturing a piece of the template
     */
    protected function endCapture()
    {
        if (count($this->captureStack) == 0) {
            throw new \Exception('Unexpected \'endCapture\'');
        }

        list($name, $append) = array_pop($this->captureStack);
        self::$captures[$name] = ($append ? $this->getCapture($name) . "\n" : '') . ob_get_contents();
        ob_end_clean();
    }

    /**
     * Gets the captured content identified by the given name
     *
     * @param string $name The name of the captured content
     * @return string The captured content
     */
    protected function getCapture($name)
    {
        return isset(self::$captures[$name]) ? self::$captures[$name] : '';
    }

    /**
     * Sets a globally accessible variable
     *
     * @param string $name The name of the variable
     * @param mixed $value The value
     */
    public function setGlobal($name, $value)
    {
        self::$globals[$name] = $value;
    }

    /**
     * Gets a globally accessible variable
     *
     * @param string $name The name of the variable
     * @param mixed $defaultValue The default value to return if the variable was not set
     * @return mixed The value
     */
    public function getGlobal($name, $defaultValue = null)
    {
        return isset(self::$globals[$name]) ? self::$globals[$name] : $defaultValue;
    }

    /**
     * Gets the path to an asset
     *
     * @param string $asset The relative path to the asset
     * @return string The real path to the asset
     */
    public function getAssetPath($asset)
    {
        return '/assets/' . $this->getTheme() . '/' . $asset;
    }

    /**
     * Inserts a link to an asset. This method will choose between a LINK,
     * SCRIPT or IMG tag based on the extension of the asset. The tag name can
     * be overruled by the second parameter
     *
     * @param string $asset The path to the asset
     * @param array $attributes Additional attributes for the tag
     * @param string $tagName The tag name. Specify one to overrule the automatically chosen tag name
     * @return string The HTML tag to link to the asset
     */
    public function asset($asset, $attributes = [], $tagName = null)
    {
        $ext = pathinfo($asset, PATHINFO_EXTENSION);
        $path = $this->getAssetPath($asset);

        if (is_string($attributes) && !$tagName) {
            $tagName = $attributes;
            $attributes = [];
        }

        if (!$tagName) {
            if ($ext == 'css' || $ext == 'ico') {
                $tagName = 'link';
            } else if ($ext == 'js') {
                $tagName = 'script';
            } else {
                $tagName = 'img';
            }
        }

        switch ($tagName) {
            case 'link':
                if ($ext == 'ico') {
                    if (!isset($attributes['type'])) $attributes['rel'] = 'shortcut icon';
                    if (!isset($attributes['type'])) $attributes['type'] = 'image/x-icon';
                } else {
                    if (!isset($attributes['type'])) $attributes['rel'] = 'stylesheet';
                    if (!isset($attributes['type'])) $attributes['type'] = 'text/css';
                }
                $attributes['href'] = $path;
                break;

            case 'script':
            case 'img':
                $attributes['src'] = $path;
                break;
        }

        $html = '<' . $tagName;
        foreach ($attributes as $key => $value) {
            $html .= ' ' . $key . '="' . $value . '"';
        }
        $html .= $tagName == 'script' ? '></script>' : ' />';

        return $html;
    }
}