<?php
namespace App;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class Filesystem
{
    public $target_dir;
    public $source_dir;

    public function __construct(App &$app)
    {
        $this->app = $app;
    }

    public function basepath()
    {
        $basepath = '.';
        if (!empty($pharPath = \Phar::running(false))) {
            $basepath = dirname($pharPath);
        }
        return $basepath;
    }
    
    public function slugify($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? false : $text;
    }

    public function create_directory($path = '')
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Replace placeholders in files
     */
    public function process_file($filename, $replace)
    {
        file_put_contents(
            $this->target_dir.'/'.$filename,
            preg_replace_callback("/{{([\w_]{1,})}}/", function ($match) use ($replace) {
                return array_key_exists($match[1], $replace) ? $replace[$match[1]] : '';
            }, file_get_contents($this->source_dir.'/'.$filename))
        );
    }

    public function create_file($path, $data)
    {
        file_put_contents($path, $data);
    }

    public function load_yaml_file($path, $required = true)
    {
        $return = [];
        if (file_exists($path)) {
            try {
                $return = Yaml::parseFile($path);
            } catch (ParseException $e) {
                $this->app->cli->error(
                    sprintf(
                        'Error: unable to parse %s near: %s line %s.',
                        $path,
                        $e->getSnippet(),
                        $e->getParsedLine()
                    )
                );
                if ($required) {
                    exit;
                }
            }
        }
        return $return;
    }
    
    public function load_yaml_string($string, $required = true)
    {
        $return = [];
        try {
            $return = Yaml::parse($string);
        } catch (ParseException $e) {
            $this->app->cli->error(
                sprintf(
                    'Error: unable to parse yaml string near: %s line %s.',
                    $e->getSnippet(),
                    $e->getParsedLine()
                )
            );
            if ($required) {
                exit;
            }
        }
        return $return;
    }

}