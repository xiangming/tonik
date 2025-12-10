<?php

/*
|-----------------------------------------------------------
| Create the Theme
|-----------------------------------------------------------
|
| Create a new Theme instance which behaves as singleton.
| This allows for easily bind and resolve various
| parts across all theme components.
|
*/

$theme = Tonik\Gin\Foundation\Theme::getInstance();


/*
 |-----------------------------------------------------------
 | Bind Theme Config
 |-----------------------------------------------------------
 |
 | We need to bind configs like theme's paths, directories and
 | files to autoload. These values will be used by the rest
 | of theme components like assets, templates etc.
 |
 */

$config = require __DIR__ . '/../config/app.php';

$theme->bind('config', function () use ($config) {
    return new Tonik\Gin\Foundation\Config($config);
});


/*
 |-----------------------------------------------------------
 | Load Project-Specific Code
 |-----------------------------------------------------------
 |
 | Load the active project's custom code based on configuration.
 | Projects are loaded after core components but before autoloader runs.
 |
 */

$project_config = require __DIR__ . '/../config/projects.php';
$active_project = $project_config['active'];

if (!empty($active_project)) {
    $project_bootstrap = $project_config['path'] . '/' . $active_project . '/bootstrap.php';
    
    if (file_exists($project_bootstrap)) {
        require_once $project_bootstrap;
    }
}


/*
 |-----------------------------------------------------------
 | Return the Theme
 |-----------------------------------------------------------
 |
 | Here we return the theme instance. Later, this instance
 | is used for autoload all theme's core component.
 |
 */

return $theme;
