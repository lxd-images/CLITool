## LXD Images (.phar tool)

This tool is used to quickly push and pull LXD images from (lxd-images) GitHub.

Its very much a WIP/PoC, dont take it too seriously just yet.

## Download

You can find prebuilt versions in [releases](...), or simply do wget as shown below:

`get ... .phar`

## Install

Git clone this project or download a prebuilt verion:

``` bash
$ git clone git@github.com:phppackage/package-generator.git . && composer install
```

## Build

To build the `package-generator.phar` run:

`bash /usr/bin/php -c /etc/php/7.0/cli/php.ini -f box.phar build -v`

## Run

`/usr/bin/php package-generator.phar -w`

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

 - [Lawrence Cherone](http://github.com/lcherone)
 - [All Contributors](../../contributors)


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
