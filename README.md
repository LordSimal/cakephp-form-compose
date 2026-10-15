# FormCompose

[![Packagist](https://img.shields.io/packagist/v/josbeir/cakephp-form-compose?label=Packagist)](https://packagist.org/packages/josbeir/cakephp-form-compose)
[![CI](https://github.com/josbeir/cakephp-form-compose/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/josbeir/cakephp-form-compose/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/josbeir/cakephp-form-compose/branch/main/graph/badge.svg)](https://app.codecov.io/gh/josbeir/cakephp-form-compose)

**A replacement for CakePHP's FormHelper, with PHP element templates.**

FormCompose keeps form data binding separate from markup. A `control()` call
renders a PHP element where you arrange the field's label, input, help, and errors.
Reuse that layout across forms, or compose an individual field directly in a view.

Use application-owned themes for Tailwind, daisyUI, Bootstrap, or your own design
system. CakePHP contexts, validation metadata, routing, and form protection remain
part of the form-building workflow.

**[Read the documentation →](https://josbeir.github.io/cakephp-form-compose/)**

- [Why FormCompose?](https://josbeir.github.io/cakephp-form-compose/why.html)
- [Install and build your first form](https://josbeir.github.io/cakephp-form-compose/getting-started.html)
- [Declare reusable form blueprints](https://josbeir.github.io/cakephp-form-compose/blueprints.html)
- [Create a PHP template theme](https://josbeir.github.io/cakephp-form-compose/themes.html)

Experimental API. Requires PHP 8.5+ and CakePHP 6 development builds. See the installation guide for setup and compatibility notes.

Contributor commands and documentation-site setup are in the
[contributing guide](https://josbeir.github.io/cakephp-form-compose/contributing.html).

[MIT License](LICENSE)
