<?php
namespace AdminAddonUserManager\Users;

use Grav\Common\Grav;
use Grav\Plugin\AdminAddonUserManagerPlugin;
use Grav\Common\Assets;
use Grav\Common\Data\Blueprints;
use RocketTheme\Toolbox\Event\Event;
use AdminAddonUserManager\Manager as IManager;
use AdminAddonUserManager\Pagination\ArrayPagination;
use Grav\Common\Utils;
use Grav\Common\User\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use AdminAddonUserManager\Group;
use AdminAddonUserManager\Dot;

class Manager implements IManager, EventSubscriberInterface {

  private $grav;
  private $plugin;
  private $adminController;

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

  public static $instance;

  public function __construct(Grav $grav, AdminAddonUserManagerPlugin $plugin) {
    $this->grav = $grav;
    $this->plugin = $plugin;

    self::$instance = $this;

    $this->grav['events']->addSubscriber($this);
  }

  public static function getSubscribedEvents() {
    return [
      'onAdminControllerInit' => ['onAdminControllerInit', 0],
      'onAdminData' => ['onAdminData', 0]
    ];
  }

  public function onAdminControllerInit($e) {
    $controller = $e['controller'];
    $this->adminController = $controller;
  }

  public function onAdminData($e) {
    $type = $e['type'];

    if (preg_match('|user-manager/|', $type)) {
      $post = $this->adminController->data;

      $obj = User::load(preg_replace('|user-manager/|', '', $type));
      $obj->merge($post);

      $e['data_type'] = $obj;
    }
  }

  /**
   * Returns the required permission to access the manager
   *
   * @return string
   */
  public function getRequiredPermission() {
    return $this->plugin->name . '.users';
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
      'label' => 'PLUGIN_ADMIN_ADDON_USER_MANAGER.USER_MANAGER',
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
    $assets->addCss('plugin://' . $this->plugin->name . '/assets/users/style.css');
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
      $username = $this->grav['uri']->paths()[2];
      if ($this->removeUser($username)) {
        $this->adminController->setRedirect($this->getLocation());
      }
    } elseif ($method === 'taskUserLoginAs') {
      $username = $this->grav['uri']->paths()[2];
      $user = User::load($username);
      $user->authenticated = true;

      $this->grav['session']->user = $user;
      unset($this->grav['user']);
      $this->grav['user'] = $user;

      $this->adminController->setRedirect('/');
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
    $uri = $this->grav['uri'];

    $user = $this->grav['uri']->paths();
    if (count($user) == 3) {
      $user = $user[2];
    } else {
      $user = false;
    }

    if ($user) {
      if (isset($_POST['task']) && $_POST['task'] === 'admin-addon-user-manager-save') {
        $user = User::load($user);
        $post = $_POST['data'];

        try {
          $user->merge($post);
          $user->validate();
          $user->filter();
          $user->save();
        } catch (\Exception $e) {
          $this->grav['admin']->setMessage($e->getMessage(), 'error');
        }

        $this->grav->redirect($this->plugin->getPreviousUrl());
      } else {
        $blueprints = new Blueprints;
        $blueprint = $blueprints->get('user/aaum-account');
        $vars['blueprints'] = $blueprint;
        $vars['user'] = $user = User::load($user);
        $vars['exists'] = $user->exists();
      }
    } else {
      // Bulk actions
      if (isset($_POST['selected'])) {
        $usernames = $_POST['selected'];

        if (isset($_POST['bulk_delete'])) {
          // Bulk delete
          foreach ($usernames as $username) {
            $this->removeUser($username);
          }

          $this->grav->redirect($this->plugin->getPreviousUrl());
        } else if (isset($_POST['bulk_add_to_group']) && isset($_POST['groups'])) {
          // Bulk add users to groups
          $groups = $_POST['groups'];

          foreach ($usernames as $username) {
            $user = User::load($username);
            if ($user->file()->exists()) {
              if (!isset($user['groups']) || !is_array($user['groups'])) {
                $user['groups'] = [];
              }

              $user['groups'] = array_unique(array_merge($user['groups'], $groups));
              $user->save();
            }
          }

          $this->grav->redirect($this->plugin->getPreviousUrl());
        } else if (isset($_POST['bulk_remove_from_group']) && isset($_POST['groups'])) {
          // Bulk remove users from groups
          $groups = $_POST['groups'];

          foreach ($usernames as $username) {
            $user = User::load($username);
            if ($user->file()->exists()) {
              if (!isset($user['groups']) || !is_array($user['groups'])) {
                $user['groups'] = [];
              }

              $user['groups'] = array_unique(array_diff($user['groups'], $groups));
              $user->save();
            }
          }

          $this->grav->redirect($this->plugin->getPreviousUrl());
        } else if (isset($_POST['bulk_add_acl']) && isset($_POST['permissions'])) {
          // Bulk add permissions to users
          $access = [];
          foreach ($_POST['permissions'] as $p) {
            Dot::set($access, $p, true);
          }

          foreach ($usernames as $username) {
            $user = User::load($username);
            if ($user->file()->exists()) {
              if (!isset($user['access']) || !is_array($user['access'])) {
                $user['access'] = [];
              }

              $user['access'] = array_merge_recursive($user['access'], $access);
              $user->save();
            }
          }

          $this->grav->redirect($this->plugin->getPreviousUrl());
        } else if (isset($_POST['bulk_remove_acl']) && isset($_POST['permissions'])) {
          // Bulk remove permissions from users
          foreach ($usernames as $username) {
            $user = User::load($username);
            if ($user->file()->exists()) {
              if (!isset($user['access']) || !is_array($user['access'])) {
                $user['access'] = [];
              }

              $access = $user['access'];
              foreach ($_POST['permissions'] as $p) {
                Dot::delete($access, $p);
              }
              $user['access'] = $access;
              $user->save();
            }
          }

          $this->grav->redirect($this->plugin->getPreviousUrl());
        }
      }

      $vars['fields'] = $this->plugin->getModalsConfiguration()['add_user']['fields'];
      $vars['bulkFields'] = $this->plugin->getModalsConfiguration()['bulk_user']['fields'];
      $vars['groupnames'] = Group::groupNames();
      $permissions = array_keys($this->grav['admin']->getPermissions());
      foreach ($permissions as $k=>&$v) $v = ['text' => $v, 'value' => $v];
      $vars['permissions'] = $permissions;

      // List style (grid or list)
      $listStyle = $uri->param('listStyle');
      if ($listStyle !== 'grid' && $listStyle !== 'list') {
        $listStyle = $this->plugin->getPluginConfigValue('default_list_style', 'grid');
      }
      $vars['listStyle'] = $listStyle;

      $users = $this->users();

      // Filtering
      $filterException = false;
      $filter = (empty($_GET['filter'])) ? '' : $_GET['filter'];
      $vars['filter'] = $filter;
      if ($filter) {
        try {
          $language = new ExpressionLanguage();
          $language->addFunction(ExpressionFunction::fromPhp('count'));
          foreach ($users as $k => $user) {
            if (!is_array($user->groups)) {
              $user->groups = [];
            }

            if (!$language->evaluate($_GET['filter'], ['user' => $user])) {
              unset($users[$k]);
            }
          }
        } catch (\Exception $exception) {
          $vars['filterException'] = $exception;
          $filterException = true;
        }
      }

      if ($filterException) {
        $users = [];
      }

      // Pagination
      $perPage = $this->plugin->getPluginConfigValue('pagination.per_page', 10);
      $pagination = new ArrayPagination($users, $perPage);
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
    }

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
    $cacheKey = $this->plugin->name . '.users';

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
    $cacheKey = $this->plugin->name . '.users';
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

  public function removeUser($username) {
    $user = User::load($username);

    if ($user->file()->exists()) {
      $users = $this->users();
      $user->file()->delete();
      // Prevent users cache refresh
      unset($users[$username]);
      $this->saveUsersToCache($users);
      return true;
    }

    return false;
  }

  public static function userNames() {
    $instance = self::$instance;
    $users = $instance->users();

    $userNames = [];
    foreach ($users as $u) {
      $userNames[$u['username']] = $u['username'];
    }

    return $userNames;
  }

}
