<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use \Grav\Common\Utils;
use \Grav\Common\User\User;

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

  /**
   * In-memory caching for users
   *
   * @var Array<User>
   */
  private $usersCached = null;

  /**
   * In-memory cache of the account directory
   *
   * @var String
   */
  private $accountDirCached = null;

  public function getConfigKey() {
    return 'plugins.' . self::SLUG;
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

    $this->enable([
      'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0],
      'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
      'onAdminMenu' => ['onAdminMenu', 0],
      'onAssetsInitialized' => ['onAssetsInitialized', 0],
      'onAdminTaskExecute' => ['onAdminTaskExecute', 0],
    ]);
  }

  public function onAssetsInitialized() {
    $this->grav['assets']->addCss('plugin://' . self::SLUG . '/assets/style.css');
  }

  public function onAdminMenu() {
    $twig = $this->grav['twig'];
    $twig->plugins_hooked_nav = (isset($twig->plugins_hooked_nav)) ? $twig->plugins_hooked_nav : [];
    $twig->plugins_hooked_nav['User Manager'] = [
      'location' => self::PAGE_LOCATION,
      'icon' => 'fa-user',
      'authorize' => 'admin.users',
      'badge' => [
        'count' => count($this->users())
      ]
    ];
  }

  public function onAdminTwigTemplatePaths($e) {
    $paths = $e['paths'];
    $paths[] = __DIR__ . DS . 'templates';
    $e['paths'] = $paths;
  }

  public function onTwigSiteVariables() {
    $twig = $this->grav['twig'];
    $page = $this->grav['page'];
    $uri = $this->grav['uri'];

    if ($page->slug() !== self::PAGE_LOCATION) {
      return;
    }

    $this->grav['session']->{self::SLUG . '.previous_url'} = $uri->route() . $uri->params();

    $page = $this->grav['admin']->page(true);
    $twig->twig_vars['context'] = $page;
    $twig->twig_vars['fields'] = $this->config->get($this->getConfigKey() . '.modal.fields');

    // List style (grid or list)
    $listStyle = $uri->param('listStyle');
    if ($listStyle !== 'grid' && $listStyle !== 'list') {
      $listStyle = 'grid';
    }
    $twig->twig_vars['listStyle'] = $listStyle;

    // Pagination
    $perPage = 10;
    $pageNumber = $uri->param('page');
    if (!$pageNumber) {
      $pageNumber = 1;
    }

    // Users
    $users = $this->users();
    $usersCount = count($users);
    $pages = ceil($usersCount / $perPage);
    // Make sure the page parameter is valid
    if ($pageNumber < 1) {
      $pageNumber = 1;
    }

    if ($pageNumber > $pages) {
      $pageNumber = $pages;
    }
    $offset = $perPage * ($pageNumber - 1);

    $twig->twig_vars['pagination'] = [
      'current' => $pageNumber,
      'count' => $pages,
      'total' => $usersCount,
      'perPage' => $perPage,
      'offset' => $offset,
    ];
    $twig->twig_vars['users'] = array_slice($users, $offset, $perPage);
  }

  public function onAdminTaskExecute($e) {
    $method = $e['method'];

    if ($method === 'taskUserDelete') {
      $page = $this->grav['admin']->page(true);
      $username = $this->grav['uri']->paths()[2];
      $user = User::load($username);

      if ($user->file()->exists()) {
        $users = $this->users();
        $user->file()->delete();
        // Prevent users cache refresh
        unset($users[$username]);
        $this->saveUsersToCache($users);
        $this->grav->redirect($this->grav['session']->{self::SLUG . '.previous_url'});
        return true;
      }
    }

    return false;
  }

  public function users() {
    if ($this->usersCached) {
      return $this->usersCached;
    }

    $users = [];
    $dir = $this->getAccountDir();

    // Try cache
    $cache =  $this->grav['cache'];
    $cacheKey = self::SLUG . '.users';

    $modifyTime = filemtime($dir);
    $usersCache = $cache->fetch($cacheKey);
    if (!$usersCache || $modifyTime > $usersCache['modifyTime']) {
      // Find accounts
      $files = $dir ? array_diff(scandir($dir), ['.', '..']) : [];
      foreach ($files as $file) {
        if (Utils::endsWith($file, YAML_EXT)) {
          $user = User::load(trim(pathinfo($file, PATHINFO_FILENAME)));
          $users[$user->username] = $user;
        }
      }

      // Populate and/or refresh cache
      $this->saveUsersToCache($users);
    } else {
      $users = $usersCache['users'];
    }

    $this->usersCached = $users;

    return $users;
  }

  private function saveUsersToCache($users) {
    $cache =  $this->grav['cache'];
    $cacheKey = self::SLUG . '.users';
    $dir = $this->getAccountDir();
    $modifyTime = filemtime($dir);

    $usersCache = [
      'modifyTime' => $modifyTime,
      'users' => $users,
    ];

    $cache->save($cacheKey, $usersCache);
  }

  private function getAccountDir() {
    if ($this->accountDirCached) {
      return $this->grav['locator']->findResource('account://');
    }

    return $this->accountDirCached = $this->grav['locator']->findResource('account://');
  }

}
