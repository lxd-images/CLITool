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

    /**
     * Load github.yaml
     * - If username/password passed as params it does not load from config
     *   For that to happen, cli tool must be run like: 
     *    - php lxd-images.phar -u github_username -p github_password
     */
    public function config($username = null, $password = null)
    {
        $github = [
            'username' => $username,
            'password' => $password
        ];
        
        if (!empty($github['username']) && !empty($github['password'])) {
            return $github;
        }

        $basepath = $this->app->filesystem->basepath();

        $github = $this->app->filesystem->load_yaml_file(
            $basepath.'/github.yaml'
        ) + $github;

        return $github;
    }

    public function save_config($filename, $data = [])
    {
        $this->app->filesystem->create_file(
            $this->app->filesystem->basepath().'/'.$filename.'.yaml',
            Yaml::dump($data)
        );
    }

    public function wizard($arguments = [])
    {
        $github = $this->config(
            ($arguments['username'] ?? null),
            ($arguments['password'] ?? null)
        );

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

        // save
        if ($this->app->cli->confirm('Would you like to save these details for next time?')->confirmed()) {
            $this->save_config('github', $github);
        }

        return $github;
    }

    public function get_images()
    {
        // check cache file, cached for 1 hour
        $images_cache = $this->app->filesystem->basepath().'/images.yaml';
        if (file_exists($images_cache)) {
            if (time() - filemtime($images_cache) < 3600) {
                return (array) $this->app->filesystem->load_yaml_file(
                    $images_cache,
                    false
                );
            }
        }
        
        $client = new \Github\Client();
        $repos = $client->api('user')->repositories('lxd-images');

        $images = [];
        foreach ($repos as $repo) {
            $yaml = @file_get_contents(
                'https://raw.githubusercontent.com/'.$repo['full_name'].'/master/image.yaml'
            );

            if (empty($yaml)) {
                continue;
            }

            $image_yaml = $this->app->filesystem->load_yaml_string($yaml, false);
            if (empty($image_yaml)) {
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

        return $images;
    }
}