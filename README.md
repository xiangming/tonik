# Chu — WordPress Theme

初创主题，基于 [Tonik — WordPress Starter Theme](http://labs.tonik.pl/theme/) 创建，通过 WordPress 的 REST API 来提供容易使用的接口。

如果对 Tonik 不熟悉，可以参考:

- [https://github.com/overtrue/api.yike.io/](https://github.com/overtrue/api.yike.io/)
- [https://github.com/calibur-tv/Hentai](https://github.com/calibur-tv/Hentai)

## 如何使用

### 安装 PHP 依赖

```bash
composer update
composer install
```

### 安装前端依赖

```bash
yarn
```

### 生成主题文件

```bash
yarn dev
# 或者
yarn prod
```

> 注意！如果不生成主题文件，将导致严重错误，进而网站无法访问。

## Tonik 功能

Tonik 主要是让 WordPress 开发更现代化。

Take a look at what is waiting for you:

- [ES6](https://babeljs.io/learn-es2015/) for JavaScript
- [SASS](http://sass-lang.com/) preprocessor for CSS
- [Webpack](https://webpack.js.org/) for managing, compiling and optimizing theme's asset files
- Simple [CLI](https://github.com/tonik/cli) for quickly initializing a new project
- Ready to use front-end boilerplates for [Foundation](//foundation.zurb.com/sites.html), [Bootstrap](//getbootstrap.com/docs/3.3/), [Bulma](//bulma.io/) and [Vue](//vuejs.org/)
- Utilizes PHP [Namespaces](http://php.net/manual/pl/language.namespaces.php)
- Simple [Theme Service Container](http://symfony.com/doc/2.0/glossary.html#term-service-container)
- Child Theme friendly [Autoloader](https://en.wikipedia.org/wiki/Autoload)
- Readable and centralized Theme Configs
- Oriented for building with [Actions](https://codex.wordpress.org/Glossary#Action) and [Filters](https://codex.wordpress.org/Glossary#Filter)
- Enhanced [Templating](https://en.wikibooks.org/wiki/PHP_Programming/Why_Templating) with support for passing data

### Requirements

Tonik Starter Theme follows [WordPress recommended requirements](https://wordpress.org/about/requirements/). Make sure you have all these dependences installed before moving on:

- WordPress >= 4.7
- PHP >= 7.0
- [Composer](https://getcomposer.org)
- [Node.js](https://nodejs.org)

### Documentation

Comprehensive documentation of the starter is available at <http://labs.tonik.pl/theme/>
