<?php
namespace App;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class GitHub
{

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
    
    public function config()
    {
        $github = [
            'username' => '',
            'password' => '',
            'organization' => ''
        ];
        
        $basepath = $this->basepath();
        
        if (file_exists($basepath.'/github.yaml')) {
            try {
                $github = Yaml::parseFile($basepath.'/github.yaml')+$github;
            } catch (ParseException $e) {
                $this->app->cli->error(
                    sprintf(
                        'Error: unable to parse github.yaml near: %s line %s.', 
                        $e->getSnippet(), 
                        $e->getParsedLine()
                    )
                );
                exit;
            }
        }
        
        return $github;
    }
    
    public function save_config($filename, $data = [])
    {
        file_put_contents(
            $this->basepath().'/'.$filename.'.yaml',
            Yaml::dump($data)
        );
    }
    
    public function wizard()
    {
        $github = $this->config();
        
        if (!empty($github['username']) && !empty($github['password'])) {
            return $github;
        }

        // username
        $github['username'] = $this->app->cli->prompt(
            'Enter github username:',
            (!empty($github['username']) ? $github['username'] : '')
        );
        
        // password
        $github['password'] = $this->app->cli->prompt(
            'Enter github password:',
            (!empty($github['password']) ? $github['password'] : '')
        );
        
        // organization
        $github['organization'] = $this->app->cli->prompt(
            'Enter github organization:',
            (!empty($github['organization']) ? $github['organization'] : '')
        );
        
        // save
        if ($this->app->cli->confirm('Would you like to save these details for next time?')->confirmed()) {
            $this->save_config('github', $github);
        }

        return $github;
    }
    
    public function get_images()
    {
        $client = new \Github\Client();
        $repos = $client->api('user')->repositories('lxd-images');
        
        $images = [];
        foreach ($repos as $repo) {
            $yaml = file_get_contents('https://raw.githubusercontent.com/'.$repo['full_name'].'/master/image.yaml');
            if (empty($yaml)) {
                continue;
            }
            try {
                $image_yaml = Yaml::parse($yaml);
            } catch (ParseException $e) {
                $this->app->cli->error(
                    sprintf(
                        'Error: unable to parse repository image.yaml near: %s line %s.', 
                        $e->getSnippet(), 
                        $e->getParsedLine()
                    )
                );
                continue;
            }
            $images[] = [
                'name' => $repo['name'],
                'full_name' => $repo['full_name'],
                'description' => $repo['description'],
                'fingerprint' => $image_yaml['fingerprint'],
                'uploaded_at' => $image_yaml['uploaded_at'],
                'size' => $image_yaml['size'],
                'html_url' => $repo['html_url'],
                'ssh_url' => $repo['ssh_url'],
                'clone_url' => $repo['clone_url']
            ];
        }
        
        $this->save_config('images', $images);
        
        return $repos;
    }
}