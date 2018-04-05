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

        $this->filesystem = new Filesystem($this);
        $this->cli = new Cli($this);

        $this->github = new GitHub($this);
        $this->lxd = new LXD($this);
        $this->config = new Config($this);
    }

    public function run()
    {
        $this->cli->clear();
        $this->cli->arguments();

        // get users github details
        $github = $this->github->wizard(
            $this->arguments
        );

        $action = $this->cli->radio(
            'What would you like to do?',
            ['Pull Image', 'Push Image']
        );

        $basepath = $this->filesystem->basepath();

        if ($action === 'Pull Image') {
            $images = $this->github->get_images();

            $image = $this->cli->radio(
                'Which image would you like to pull?',
                array_column($images, 'name')
            );

            foreach ($images as $item) {
                if ($item['name'] === $image) {
                    $image = $item;
                    break;
                }
            }
            
            if (empty($image['name'])) {
                $this->cli->error('You must select an image.');
                die();
            }

            $workingDir = $basepath.'/images/'.$image['name'];

            if (!file_exists($workingDir)) {
                $this->cli->out('<green>Created working directory: '.$workingDir.'</green>');
                mkdir($workingDir, 0755, true);
            }

            chdir($workingDir);

            $this->cli->out('<green>Cloning: '.$image['clone_url'].'</green>');

            `git clone {$image['clone_url']} .`;

            $this->cli->out('<green>Importing image.</green>');

            `bash import.sh && rm "$workingDir" -Rf`;

            if (file_exists($workingDir)) {
                $this->cli->out('<green>Cleaning up.</green>');
                `rm "$workingDir" -Rf`;
            }

            if ($this->cli->confirm('Would you like to create a container?')->confirmed()) {
                $this->cli->out('<green>Launching container: '.$image['name'].'</green>');
                `lxc launch {$image['fingerprint']} "{$image['name']}"`;
            }

            if (file_exists($basepath)) {
                chdir($basepath);
            }

            exit();
        }

        if ($action === 'Push Image') {

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
            
            if (empty($image)) {
                $this->cli->error('You must select an image.');
                die();
            }
            
            $safefilename = $this->filesystem->slugify($image);

            $workingDir = $basepath.'/images/'.$safefilename;

            if (!file_exists($workingDir)) {
                mkdir($workingDir, 0755, true);
                mkdir($workingDir.'/src', 0755, true);
            }

            $fingerprint = array_search($image, $options);

            `/usr/bin/lxc config set images.compression_algorithm gzip`;

            `/usr/bin/lxc image export $fingerprint "$workingDir/$safefilename"`;

            if (file_exists($workingDir.'/'.$safefilename.'.root')) {
                $default_images[] = $fingerprint;
                file_put_contents(
                    $basepath.'/default_images.yaml',
                    Yaml::dump($default_images)
                );
                `rm "$workingDir" -Rf`;
                $this->cli->error('Default images cannot be exported. Image has been added to list and will no longer be show.');
                exit;
            }

            if (file_exists($workingDir.'/'.$safefilename.'.tar.bz2')) {
                $type = 'bz2';
                `split --bytes 50M --numeric-suffixes --suffix-length=3 "$workingDir/$safefilename".tar.bz2 "$workingDir/src/$safefilename".tar.bz2.`;
            }

            if (file_exists($workingDir.'/'.$safefilename.'.tar.gz')) {
                $type = 'gz';
                `split --bytes 50M --numeric-suffixes --suffix-length=3 "$workingDir/$safefilename".tar.gz "$workingDir/src/$safefilename".tar.gz.`;
            }

            chdir($workingDir.'/src');

            `git init`;

            file_put_contents('README.md', '# '.ucfirst($image).'

 - Image fingerprint: `'.$fingerprint.'`

## Import & Installation

- `git clone https://github.com/lxd-images/'.$safefilename.'.git`
- `cd '.$safefilename.'`
- `./import.sh`

## Create Container

 - `lxc launch '.substr($fingerprint, 0, 12).' '.$safefilename.'`

Generated by LXD-Images.phar
');

            file_put_contents('image.yaml', Yaml::dump($images[$fingerprint]));
            file_put_contents('import.sh', '#!/bin/bash

cat '.$safefilename.'.tar.'.$type.'.* > '.$safefilename.'.tar.'.$type.'

lxc image import '.$safefilename.'.tar.'.$type);

            `git add -A ./`;
            `git commit -a -m "Initial Commit"`;

            $client = new \Github\Client();
            $client->authenticate($github['username'], $github['password'], \Github\Client::AUTH_HTTP_PASSWORD);

            $repo = $client->api('repo')->create($safefilename, 'LXD Image: '.$image, '', true, ($github['organization'] ?? null));

            `git remote add origin {$repo['ssh_url']}`;

            `git push -u origin master`;

            chdir($basepath);
            
            if (file_exists('images.yaml')) {
                unlink('images.yaml');
            }

            `rm "$workingDir" -Rf`;

            exit('Image pushed: '.$repo['html_url']);
        }

    }

}
