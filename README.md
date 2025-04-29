# Fake PHP functions

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-ga]
![Coverage Status][ico-coverage]
[![Total Downloads][ico-downloads]][link-downloads]

This package provides a particularly useful approach for testing code that heavily relies on PHP built-in functions or language constructs that are normally challenging to test (can't mock it).

## Requirements

* PHP >= 7.2

## Installation

This package is installable and autoloadable via Composer as [filisko/testable-phpfunctions](https://packagist.org/packages/filisko/testable-phpfunctions).

```sh
composer require filisko/testable-phpfunctions
```

## Usage

TODO

## Other testing utilities

- PSR-3 fake logger: [filisko/fake-psr3-logger](https://github.com/filisko/fake-psr3-logger)
- PSR-15 middleware dispatcher: [middlewares/utils](https://github.com/middlewares/utils?tab=readme-ov-file#dispatcher) (used in conjuction with PSR-7 and PSR-17)
- PSR-16 fake cache: [kodus/mock-cache](https://github.com/kodus/mock-cache)

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/filisko/testable-phpfunctions.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ga]: https://github.com/filisko/testable-phpfunctions/workflows/testing/badge.svg
[ico-coverage]: https://coveralls.io/repos/github/filisko/testable-phpfunctions/badge.svg?branch=main
[ico-downloads]: https://img.shields.io/packagist/dt/filisko/testable-phpfunctions.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/filisko/testable-phpfunctions
[link-downloads]: https://packagist.org/packages/filisko/testable-phpfunctions
