## LXD Images (.phar tool)

This tool is used to quickly push and pull LXD images from (lxd-images) GitHub.

Its very much a WIP/PoC, dont take it too seriously just yet.

## Download

You can find prebuilt versions in [releases](https://github.com/lxd-images/CLITool/releases), or simply do wget as shown below:

`wget https://github.com/lxd-images/CLITool/raw/master/lxd-images.phar`

## Run

`/usr/bin/php lxd-images.phar -w`

## Dev

In case you wanted to contribute or convert for your own needs (if you do at least star this project to show your love).

### Clone & Build

``` bash
$ git clone git@github.com:lxd-images/CLITool.git . && composer install
```

To build the `lxd-images.phar` run:

`bash /usr/bin/php -c /etc/php/7.0/cli/php.ini -f box.phar build -v`

or:

`bash dev-build.sh`

Check in `dev-install.sh` that you got the right stuff, you might need to change PHP version etc.


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

 - [Lawrence Cherone](http://github.com/lcherone)
 - [All Contributors](../../contributors)


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.