<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use \Grav\Common\Utils;
use \Grav\Common\User\User;
use AdminAddonUserManager\Users\Manager as UsersManager;

class AdminAddonUserManagerPlugin extends Plugin {

  /**
   * Slug is used to determine configuration, cache keys and assets location
   */
  const SLUG = 'admin-addon-user-manager';

  /**
   * The location of the user manager
   * /your/site/admin/PAGE_LOCATION
   */
  const PAGE_LOCATION = 'user-manager';

  private $managers = [];

  /**
   * Returns the plugin's configuration key
   *
   * @param String $key
   * @return String
   */
  public function getPluginConfigKey($key = null) {
    $pluginKey = 'plugins.' . self::SLUG;

    return ($key !== null) ? $pluginKey . '.' . $key : $pluginKey;
  }

  public function getPluginConfigValue($key = null, $default = null) {
    return $this->config->get($this->getPluginConfigKey($key), $default);
  }

  public function getConfigValue($key, $default = null) {
    return $this->config->get($key, $default);
  }

  public function getPreviousUrl() {
    return $this->grav['session']->{self::SLUG . '.previous_url'};
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

    include __DIR__ . DS . 'src' . DS . 'Manager.php';
    include __DIR__ . DS . 'src' . DS . 'Pagination' . DS . 'Pagination.php';
    include __DIR__ . DS . 'src' . DS . 'Pagination' . DS . 'ArrayPagination.php';
    include __DIR__ . DS . 'src' . DS . 'Users' . DS . 'Manager.php';

    $this->managers[] = new UsersManager($this->grav, $this);

    $this->enable([
      'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', -10],
      'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
      'onAdminMenu' => ['onAdminMenu', 0],
      'onAssetsInitialized' => ['onAssetsInitialized', 0],
      'onAdminTaskExecute' => ['onAdminTaskExecute', 0],
    ]);
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
      if ($page->slug() === $manager->getLocation()) {
        $session->{self::SLUG . '.previous_url'} = $uri->route() . $uri->params();

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
      $result = $manager->handleTask($e);

      if ($result) {
        return true;
      }
    }

    return false;
  }

}
