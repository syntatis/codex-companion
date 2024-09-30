# 🪵 👨‍🏭 codex-companion

> [!CAUTION]
> This project is currently in active development. It is not recommended for production use *just yet*.

[![ci](https://github.com/syntatis/codex-companion/actions/workflows/ci.yml/badge.svg)](https://github.com/syntatis/codex-companion/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/syntatis/codex-companion/graph/badge.svg?token=VYW2MHLXYV)](https://codecov.io/gh/syntatis/codex-companion)

This Composer plugin provides a set of tools, like commands and scripts, designed as a supporting for projects extending functionalities from the [Codex](https://github.com/syntatis/codex).

## Projects

These are projects supported by `codex-companion`:

- [Howdy](https://github.com/syntatis/howdy)

## Installation

To install, use [Composer](https://getcomposer.org/) and require this package as a development dependency:

```bash
composer require syntatis/codex-companion --dev
```

If you're using Composer 2.2 or higher, it will [ask for permission](https://blog.packagist.com/composer-2-2/#more-secure-plugin-execution) to allow this plugin to run code. You'll need to grant this permission for the plugin to work. Once permission is granted, Composer will automatically add the following snippet to your `composer.json` file:

```json
{
	"config": {
		"allow-plugins": {
			"syntatis/codex-companion": true
		}
	}
}
```
