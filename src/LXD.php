<?php
namespace App;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class LXD
{

    public function __construct(App &$app)
    {
        $this->app = $app;
    }
    
    public function images ()
    {
        $images = (array) json_decode(`/usr/bin/lxc query /1.0/images`, true);
        
        $return = [];
        foreach ($images as $image) {
            $return[basename($image)] = (array) json_decode(`/usr/bin/lxc query $image`, true);
        }
        return $return;
    }
    
}