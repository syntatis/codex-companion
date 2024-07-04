# 🖲 composer-project-plugin

[![php](https://github.com/syntatis/composer-project-plugin/actions/workflows/php.yml/badge.svg)](https://github.com/syntatis/composer-project-plugin/actions/workflows/php.yml)

This Composer plugin provides a set of tools, like commands and scripts, designed to support Syntatis projects like the [wp-starter-plugin](https://github.com/syntatis/wp-starter-plugin).

## Installation

To install, use [Composer](https://getcomposer.org/) and require this package as a development dependency:

```bash
composer require syntatis/composer-project-plugin --dev
```

If you're using Composer 2.2 or higher, it will [ask for permission](https://blog.packagist.com/composer-2-2/#more-secure-plugin-execution) to allow this plugin to run code. You'll need to grant this permission for the plugin to work. Once permission is granted, Composer will automatically add the following snippet to your `composer.json` file:

```json
{
	"config": {
		"allow-plugins": {
			"syntatis/composer-project-plugin": true
		}
	}
}
```
