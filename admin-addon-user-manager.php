<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use \Grav\Common\Utils;
use \Grav\Common\User\User;
use Grav\Common\File\CompiledYamlFile;
use AdminAddonUserManager\Users\Manager as UsersManager;
use AdminAddonUserManager\Groups\Manager as GroupsManager;

class AdminAddonUserManagerPlugin extends Plugin {

  /**
   * Returns the plugin's configuration key
   *
   * @param String $key
   * @return String
   */
  public function getPluginConfigKey($key = null) {
    $pluginKey = 'plugins.' . $this->name;

    return ($key !== null) ? $pluginKey . '.' . $key : $pluginKey;
  }

  public function getPluginConfigValue($key = null, $default = null) {
    return $this->config->get($this->getPluginConfigKey($key), $default);
  }

  public function getConfigValue($key, $default = null) {
    return $this->config->get($key, $default);
  }

  public function getPreviousUrl() {
    return $this->grav['session']->{$this->name . '.previous_url'};
  }

  public function getModalsConfiguration() {
    return CompiledYamlFile::instance(__DIR__ . DS . 'modals.yaml')->content();
  }

  public static function getSubscribedEvents() {
    return [
      'onPluginsInitialized' => ['onPluginsInitialized', 0]
    ];
  }

  public function onPluginsInitialized() {
    if (!$this->isAdmin() || !$this->grav['user']->authenticated) {
      return;
    }

    $this->grav['locator']->addPath('blueprints', '', __DIR__ . DS . 'blueprints');

    include __DIR__ . DS . 'vendor' . DS . 'autoload.php';

    $this->managers[] = new UsersManager($this->grav, $this);
    $this->managers[] = new GroupsManager($this->grav, $this);

    $this->enable([
      'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', -10],
      'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
      'onAdminMenu' => ['onAdminMenu', 0],
      'onAssetsInitialized' => ['onAssetsInitialized', 0],
      'onAdminTaskExecute' => ['onAdminTaskExecute', 0],
    ]);

    $this->registerPermissions();
  }

  public function onAssetsInitialized() {
    $assets = $this->grav['assets'];

    foreach ($this->managers as $manager) {
      $manager->initializeAssets($assets);
    }
  }

  public function onAdminMenu() {
    $twig = $this->grav['twig'];
    $twig->plugins_hooked_nav = (isset($twig->plugins_hooked_nav)) ? $twig->plugins_hooked_nav : [];

    foreach ($this->managers as $manager) {
      $nav = $manager->getNav();
      $twig->plugins_hooked_nav[$nav['label']] = $nav;
    }
  }

  public function onAdminTwigTemplatePaths($e) {
    $paths = $e['paths'];
    $paths[] = __DIR__ . DS . 'templates';
    $e['paths'] = $paths;
  }

  public function onTwigSiteVariables() {
    $page = $this->grav['page'];
    $twig = $this->grav['twig'];
    $session = $this->grav['session'];
    $uri = $this->grav['uri'];

    foreach ($this->managers as $manager) {
      if ($page->slug() === $manager->getLocation() && $this->grav['admin']->authorize(['admin.super', $manager->getRequiredPermission()])) {
        $session->{$this->name . '.previous_url'} = $uri->route() . $uri->params();

        $page = $this->grav['admin']->page(true);
        $twig->twig_vars['context'] = $page;

        $vars = $manager->handleRequest();
        $twig->twig_vars = array_merge($twig->twig_vars, $vars);

        return true;
      }
    }
  }

  public function onAdminTaskExecute($e) {
    foreach ($this->managers as $manager) {
      if ($this->grav['admin']->authorize(['admin.super', $manager->getRequiredPermission()])) {
        $result = $manager->handleTask($e);

        if ($result) {
          return true;
        }
      }
    }

    return false;
  }

  public function registerPermissions() {
    foreach ($this->managers as $manager) {
      $this->grav['admin']->addPermissions([$manager->getRequiredPermission() => 'boolean']);
    }

    // Custom permissions
    $customPermissions = $this->getPluginConfigValue('custom_permissions', []);
    foreach ($customPermissions as $permission) {
      $this->grav['admin']->addPermissions([$permission => 'boolean']);
    }
  }

}
