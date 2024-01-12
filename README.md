# Chu — WordPress Theme

基础设施后端主题，目前实现的基础设施能力：

- [x] 短信发送
- [x] 邮件发送
- [x] 图形码
- [x] 二维码
- [x] 微信和支付宝支付
- [ ] 对象存储 token 下发

依赖此主题的产品主题，使用分支开发，方便基础代码共享：

- [x] 打赏项目：fans/master

## 分支管理

使用分支管理会降低开发效率，但是可以让产品更稳定，也方便回滚。

公共代码，跟踪代码仓库服务器：

- master: 预留，未使用。（因为公共代码没有部署的需求）
- develop: 主分支，跟踪 [origin](git@github.com:xiangming/tonik.git)的 develop 分支。
- feature/xxx: 独立功能需求开发分支，跟踪 [origin](git@github.com:xiangming/tonik.git)的 feature/xxx 分支。

fans 项目 WEB 服务器：

- 测试环境：[dev](ssh://git@165.154.22.106:7000/home/data/git/dev.git)
- 生产环境：[release](ssh://git@165.154.22.106:7000/home/data/git/release.git)

每次部署，使用 Tag 打版本号。

## 开发

基于 [Tonik — WordPress Starter Theme](http://labs.tonik.pl/theme/) 创建，通过 WordPress 的 REST API 来提供容易使用的接口。

如果对 Tonik 不熟悉，可以参考:

- [https://github.com/overtrue/api.yike.io/](https://github.com/overtrue/api.yike.io/)
- [https://github.com/calibur-tv/Hentai](https://github.com/calibur-tv/Hentai)
- [https://github.com/qingwuit/qwshop](https://github.com/qingwuit/qwshop)
- [Refactor service provider](https://github.com/tonik/theme/issues/27)

> 注意！开发功能时，尽量与业务解耦并形成独立的文件，已方便其他项目移植使用。

## 部署

以 fans 项目为例：

```bash
git checkout master
git merge develop --squash

# 部署到测试环境
git push dev master:master

# 部署到生产环境
git push release master:master
```

## 如何使用

### 安装插件

ssh 到服务器的 plugins 目录，安装依赖的插件：

```bash
cd /www/wwwroot/dev.chuchuang.work/wp-content/plugins
git clone https://github.com/fqht/wp-api-jwt-auth.git
```

### 插件配置

在 wp-config.php 里面加入：

```php
/** JWT */
define('JWT_AUTH_SECRET_KEY', '+|;le|/~n-$XyXf:mE/Ac4SOvm|]FQn&`u}010;ON1adj(J{A(nm/;;P<S6qFXI[');
define('JWT_AUTH_CORS_ENABLE', true);

/* 好了！请不要再继续编辑。请保存本文件。使用愉快！ */
```

### 安装 PHP 依赖

```bash
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
