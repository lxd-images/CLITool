<?php
namespace App;

use League\CLImate\CLImate;

class Cli
{
    private $climate;

    public function __construct(App &$app)
    {
        $this->app = $app;
        $this->climate = new CLImate;
    }
    
    private function fix_input_characters($value) {
        return trim(str_ireplace([
            "\e[A", "^[[A", '\e[A', '^[[A', 
            "\e[B", "^[[B", '\e[B', '^[[B', 
            "\e[C", "^[[C", '\e[C', '^[[C', 
            "\e[D", "^[[D", '\e[D', '^[[D', 
        ], null, $value));
    }
    
    public function out($msg)
    {
        $this->climate->out($msg);
    }
    
    public function radio ($title, $options)
    {
        return $this->climate->radio($title, $options)->prompt();
    }
    
    public function prompt($msg, $default = null)
    {
        $input = $this->app->cli->input($msg);
        $input->defaultTo($default);
        return $this->fix_input_characters($input->prompt());
    }
    
    public function input($msg)
    {
        return $this->climate->input($msg);
    }
    
    public function confirm($msg)
    {
        return $this->climate->confirm($msg);
    }
    
    public function error($msg)
    {
        return $this->climate->error($msg);
    }
    
    public function clear()
    {
        $this->climate->clear();
        echo file_get_contents(__DIR__.'/assets/logo.txt');
    }
    
    public function arguments()
    {
        $this->climate->arguments->add([
            'fingerprint' => [
                'prefix'      => 'f',
                'longPrefix'  => 'fingerprint',
                'description' => 'Import an image by fingerprint'
            ],
            'username' => [
                'prefix'      => 'u',
                'longPrefix'  => 'username',
                'description' => 'Set GitHub username'
            ],
            'password' => [
                'prefix'      => 'p',
                'longPrefix'  => 'password',
                'description' => 'Set GitHub password'
            ],
            'help' => [
                'prefix'      => 'h',
                'longPrefix'  => 'help',
                'description' => 'Prints this usage statement',
                'noValue'     => true
            ]
        ]);
        
        $this->climate->arguments->parse();

        $this->app->arguments = [
            'fingerprint' => $this->climate->arguments->get('fingerprint'),
            'username'    => $this->climate->arguments->get('username'),
            'password'    => $this->climate->arguments->get('password'),
            'help'        => $this->climate->arguments->defined('help')
        ];

        // show help
        if ($this->app->arguments['help']) {
            $this->climate->usage();
            die();
        }
    }

}