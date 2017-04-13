<?php

namespace App;

class Template
{
    /**
     * The global theme
     * @var string
     */
    public static $theme;

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
        $theme = $this->overrideTheme ? $this->overrideTheme : self::$theme;
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
}