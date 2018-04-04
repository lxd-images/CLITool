<?php
namespace App;

use PHPPackage\MagicClass;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class App extends MagicClass
{
    public function __construct()
    {
        parent::__construct();

        $this->cli = new Cli($this);
        $this->package = new Package($this);
        $this->github = new GitHub($this);
        $this->lxd = new LXD($this);
        $this->config = new Config($this);

        $this->filesystem = new Filesystem($this);
    }

    public function run()
    {
        $this->cli->clear();
        $this->cli->arguments();
        
        // get users github details
        $github = $this->github->wizard();
        
        //print_r($this->github->get_images());
        
        $action = $this->cli->radio(
            'What would you like to do?',
            ['List Images', 'Push Image']
        );
        
        if ($action === 'Push Image') {
            
            $basepath = $this->config->basepath();
            
            // default images cant be exported, for some reason exported images have .root, then upon import import empty images, only images created from containers work.. very annoying.
            $default_images = [];
            if (file_exists($basepath.'/default_images.yaml')) {
                try {
                    $default_images = Yaml::parseFile($basepath.'/default_images.yaml');
                } catch (ParseException $e) {
                    $this->app->cli->error(
                        sprintf(
                            'Error: unable to parse default_images.yaml near: %s line %s.', 
                            $e->getSnippet(), 
                            $e->getParsedLine()
                        )
                    );
                    exit;
                }
            }
            
            $images = $this->lxd->images();
            
            $options = [];
            foreach ($images as $image) {
                if (in_array($image['fingerprint'], $default_images)) {
                    continue;
                }
                if (empty($image['properties']['description'])) {
                    if (empty($image['aliases'][0]['name'])) {
                        continue;
                    }
                    $image['properties']['description'] = $image['aliases'][0]['name'];
                }
                $options[$image['fingerprint']] = $image['properties']['description'];
            }
            
            $image = $this->cli->radio(
                'Which image would you like to push?',
                array_values($options)
            );
            
            $workingDir = $basepath.'/images/'.$image;
            
            if (!file_exists($workingDir)) {
                mkdir($workingDir, 0755, true);
                mkdir($workingDir.'/src', 0755, true);
            }

            $fingerprint = array_search($image, $options);
            
            `/usr/bin/lxc config set images.compression_algorithm gzip`;

            `/usr/bin/lxc image export $fingerprint "$workingDir/$image"`;
            
            if (file_exists($workingDir.'/'.$image.'.root')) {
                $default_images[] = $fingerprint;
                file_put_contents(
                    $basepath.'/default_images.yaml',
                    Yaml::dump($default_images)
                );
                `rm "$workingDir" -Rf`;
                $this->cli->error('Default images cannot be exported. Image has been added to list and will no longer be show.');
            }

            if (file_exists($workingDir.'/'.$image.'.tar.bz2')) {
                `split --bytes 50M --numeric-suffixes --suffix-length=3 "$workingDir/$image".tar.bz2 "$workingDir/src/$image".tar.bz2.`;
            }
            
            if (file_exists($workingDir.'/'.$image.'.tar.gz')) {
                `split --bytes 50M --numeric-suffixes --suffix-length=3 "$workingDir/$image".tar.gz "$workingDir/src/$image".tar.gz.`;
            }

            chdir($workingDir.'/src');
            
            `git init`;
            
            file_put_contents('README.md', '# '.$image.'

 - Image fingerprint: `'.$fingerprint.'`

## Import & Installation

- `git clone https://github.com/lcherone/'.$image.'.git`
- `cd '.$image.'`
- `./import.sh`

## Create Container

 - `lxc launch '.substr($fingerprint, 0, 12).' '.$image.'`

Generated by LXD-Images.phar
');

            file_put_contents('image.yaml', Yaml::dump($images[$fingerprint]));
            file_put_contents('import.sh', '#!/bin/bash

cat '.$image.'.tar.gz.* > '.$image.'.tar.gz

lxc image import '.$image.'.tar.gz');

            `git add -A ./`;
            `git commit -a -m "Initial Commit"`;
            
            $client = new \Github\Client();
            $client->authenticate($github['username'], $github['password'], \Github\Client::AUTH_HTTP_PASSWORD);
        
            $repo = $client->api('repo')->create($image, 'LXD Image: '.$image, '', true, ($github['organization'] ?? null));

            `git remote add origin {$repo['ssh_url']}`;
            
            `git push -u origin master`;
            
            chdir($basepath);
            
            `rm "$workingDir" -Rf`;
            
            exit('Image pushed: '.$repo['html_url']);
        }

    }

}
