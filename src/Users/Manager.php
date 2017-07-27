<?php
namespace AdminAddonUserManager\Users;

use Grav\Common\Grav;
use Grav\Plugin\AdminAddonUserManagerPlugin;
use Grav\Common\Assets;
use RocketTheme\Toolbox\Event\Event;
use AdminAddonUserManager\Manager as IManager;
use AdminAddonUserManager\Pagination\ArrayPagination;
use \Grav\Common\Utils;
use \Grav\Common\User\User;

class Manager implements IManager {

  private $grav;
  private $plugin;

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

  public function __construct(Grav $grav, AdminAddonUserManagerPlugin $plugin) {
    $this->grav = $grav;
    $this->plugin = $plugin;
  }

  /**
   * Returns the required permission to access the manager
   *
   * @return string
   */
  public function getRequiredPermission() {
    return 'admin.users';
  }

  /**
   * Returns the location of the manager
   * It will be accessible at this path
   * 
   * @return string
   */
  public function getLocation() {
    return 'user-manager';
  }

  /**
   * Returns the plugin hooked nav array
   *
   * @return array
   */
  public function getNav() {
    return [
      'label' => 'User Manager',
      'location' => $this->getLocation(),
      'icon' => 'fa-user',
      'authorize' => $this->getRequiredPermission(),
      'badge' => [
        'count' => count($this->users())
      ]
    ];
  }

  /**
   * Initialiaze required assets
   *
   * @param \Grav\Common\Assets $assets
   * @return void
   */
  public function initializeAssets(Assets $assets) {
    $this->grav['assets']->addCss('plugin://' . $this->plugin::SLUG . '/assets/users/style.css');
  }

  /**
   * Handle task requests
   *
   * @param \RocketTheme\Toolbox\Event\Event $event
   * @return boolean
   */
  public function handleTask(Event $event) {
    $method = $event['method'];

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
        $this->grav->redirect($this->grav['session']->{$this->plugin::SLUG . '.previous_url'});
        return true;
      }
    }

    return false;
  }

  /**
   * Logic of the manager goes here
   *
   * @return array The array to be merged to Twig vars
   */
  public function handleRequest() {
    $vars = [];

    $twig = $this->grav['twig'];
    $page = $this->grav['page'];
    $uri = $this->grav['uri'];

    $this->grav['session']->{$this->plugin::SLUG . '.previous_url'} = $uri->route() . $uri->params();

    $page = $this->grav['admin']->page(true);
    $vars['context'] = $page;
    $vars['fields'] = $this->plugin->getPluginConfigValue('modal.fields');

    // List style (grid or list)
    $listStyle = $uri->param('listStyle');
    if ($listStyle !== 'grid' && $listStyle !== 'list') {
      $listStyle = $this->plugin->getPluginConfigValue('default_list_style', 'grid');
    }
    $vars['listStyle'] = $listStyle;

    // Pagination
    $perPage = $this->plugin->getPluginConfigValue('pagination.per_page', 10);
    $pagination = new ArrayPagination($this->users(), $perPage);
    $pagination->paginate($uri->param('page'));

    $vars['pagination'] = [
      'current' => $pagination->getCurrentPage(),
      'count' => $pagination->getPagesCount(),
      'total' => $pagination->getRowsCount(),
      'perPage' => $pagination->getRowsPerPage(),
      'startOffset' => $pagination->getStartOffset(),
      'endOffset' => $pagination->getEndOffset()
    ];
    $vars['users'] = $pagination->getPaginatedRows();

    return $vars;
  }

  public function users() {
    if ($this->usersCached) {
      return $this->usersCached;
    }

    $users = [];
    $dir = $this->getAccountDir();

    // Try cache
    $cache =  $this->grav['cache'];
    $cacheKey = $this->plugin::SLUG . '.users';

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
    $cacheKey = $this->plugin::SLUG . '.users';
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