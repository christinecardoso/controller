<?php

namespace Sober\Controller;

class Loader
{
    protected $path;
    protected $files;
    protected $instance;
    protected $instances = [];

    public function __construct()
    {
        $this->setPath();

        if (!file_exists($this->path)) return;

        $this->setDocumentClasses();
        $this->setFileList();

        $this->includeTraits();
        $this->includeClasses();
    }

    /**
     * Set Path
     *
     * Set the default path or get the custom path
     */
    protected function setPath()
    {
        $this->path = (has_filter('sober/controller/path') ? apply_filters('sober/controller/path', rtrim($this->path)) : get_stylesheet_directory() . '/src/controllers');
    }

    /**
     * Set File List
     *
     * Recursively get file list and place into array
     */
    protected function setFileList()
    {
        $this->files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path));
    }

    /**
     * Set Classes to Body
     *
     * @return string
     */
    protected function setDocumentClasses()
    {
        add_filter('body_class', function ($body) {
            global $wp_query;
            $templates = (new \Brain\Hierarchy\Hierarchy())->getTemplates($wp_query);
            $templates = array_reverse($templates);
            $classes[] = 'base-data';

            foreach ($templates as $template) {
                if (strpos($template, '.blade.php') || $template === 'index') continue;
                $classes[] = str_replace('.php', '-data', $template);
            }
            
            return array_merge($body, $classes);
        });
    }

    /**
     * Set Instance
     *
     * Add instance name and class to $instances[]
     */
    protected function setInstance()
    {
        $class = get_declared_classes();
        $class = '\\' . end($class);
        $template = pathinfo($this->instance, PATHINFO_FILENAME);
        $this->instances[$template] = $class;
    }

    /**
     * Is File
     *
     * Determine if the file is a PHP file (excludes directories)
     * @return boolean
     */
    protected function isFile()
    {
        return (in_array(pathinfo($this->instance, PATHINFO_EXTENSION), ['php']));
    }

    /**
     * Is File Class
     *
     * Determine if the file is a Controller Class
     * @return boolean
     */
    protected function isFileClass()
    {
       return (strstr(file_get_contents($this->instance), "extends Controller") ? true : false);
    }

    /**
     * Return Base Data
     *
     * @return array
     */
    public function getBaseData()
    {
        if (array_key_exists('base', $this->instances)) {
            return (new $this->instances['base']())->__getData();
        }
        return array();
    }

    /**
     * Return Data
     *
     * @return array
     */
    public function getData()
    {
        return $this->instances;
    }


    /**
     * Traits Loader
     *
     * Load each Trait instance
     */
    protected function includeTraits()
    {
        foreach ($this->files as $filename => $file) {
            $this->instance = $filename;
            if (!$this->isFile() || $this->isFileClass()) continue;
            include_once $filename;
        }
    }

    /**
     * Classes Loader
     *
     * Load each Class instance
     */
    protected function includeClasses()
    {
        foreach ($this->files as $filename => $file) {
            $this->instance = $filename;
            if (!$this->isFile() || !$this->isFileClass()) continue;
            include_once $filename;
            $this->setInstance();
        }
    }
}
