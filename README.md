<div align="center">
  <strong>📦 composer-project-plugin</strong>
  <p>Supporting tools for Syntatis packages</p>
</div>

---

This Composer plugin provides a set of tools, like commands and scripts, designed to support [Syntatis packages](https://packagist.org/packages/syntatis/).

## Usage

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
