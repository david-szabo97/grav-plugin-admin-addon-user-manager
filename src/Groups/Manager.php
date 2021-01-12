<?php
namespace AdminAddonUserManager\Groups;

use Grav\Common\Grav;
use Grav\Plugin\AdminAddonUserManagerPlugin;
use Grav\Common\Assets;
use RocketTheme\Toolbox\Event\Event;
use AdminAddonUserManager\Manager as IManager;
use AdminAddonUserManager\Pagination\ArrayPagination;
use \Grav\Common\Utils;
use AdminAddonUserManager\Group;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Grav\Common\User\User;
use \AdminAddonUserManager\Users\Manager as UsersManager;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class Manager implements IManager, EventSubscriberInterface {

  private $grav;
  private $plugin;
  private $adminController;

  public function __construct(Grav $grav, AdminAddonUserManagerPlugin $plugin) {
    $this->grav = $grav;
    $this->plugin = $plugin;

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

    if (preg_match('|group-manager|', $type) && ($group = $this->grav['uri']->param('name', false))) {
      $obj = Group::load($group);
      $post = $this->adminController->data;
      if (isset($post['users'])) {
        $usersInGroup = $post['users'];
        unset($post['users']);
      } else {
        $usersInGroup = [];
      }
      $obj->merge($post);
      $e['data_type'] = $obj;

      foreach (UsersManager::$instance->users() as $u) {
        $groups = $u->get('groups', []);
        if (in_array($u['username'], $usersInGroup)) {
          if (!in_array($obj['groupname'], $groups)) {
            $u['groups'] = array_merge($groups, [$obj['groupname']]);
            $u->save();
          }
        } else {
          if (in_array($obj['groupname'], $groups)) {
            $u['groups'] = array_diff($groups, [$obj['groupname']]);
            if (empty($u['groups'])) {
              unset($u['groups']);
            }
            $u->save();
          }
        }
      }
    }
  }

  /**
   * Returns the required permission to access the manager
   *
   * @return string
   */
  public function getRequiredPermission() {
    return $this->plugin->name . '.groups';
  }

  /**
   * Returns the location of the manager
   * It will be accessible at this path
   *
   * @return string
   */
  public function getLocation() {
    return 'group-manager';
  }

  /**
   * Returns the plugin hooked nav array
   *
   * @return array
   */
  public function getNav() {
    return [
      'label' => 'PLUGIN_ADMIN_ADDON_USER_MANAGER.GROUP_MANAGER',
      'location' => $this->getLocation(),
      'icon' => 'fa-group',
      'authorize' => $this->getRequiredPermission(),
      'badge' => [
        'count' => count($this->groups())
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
    $this->grav['assets']->addCss('plugin://' . $this->plugin->name . '/assets/groups/style.css');
  }

  /**
   * Handle task requests
   *
   * @param \RocketTheme\Toolbox\Event\Event $event
   * @return boolean
   */
  public function handleTask(Event $event) {
    $method = $event['method'];

    if ($method === 'taskGroupDelete' && ($group = $this->grav['uri']->param('name', false))) {
      $groupObj = Group::load($group);
      $this->grav->fireEvent('AAUM_onGroupRemove', new Event(['group' => $groupObj]));
      Group::remove($group);
      $this->grav->redirect($this->grav['uri']->url($this->getLocation()));
      return true;
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

    // Bulk actions
    if (isset($_POST['selected'])) {
      $groupnames = $_POST['selected'];

      if (isset($_POST['bulk_delete'])) {
        // Bulk delete groups
        foreach ($groupnames as $groupname) {
          $groupObj = Group::load($groupname);
          $this->grav->fireEvent('AAUM_onGroupRemove', new Event(['group' => $groupObj]));
          Group::remove($groupname);
        }

        $this->grav->redirect($this->plugin->getPreviousUrl());
      }
    }

    $group = $this->grav['uri']->param('name', false);

    if ($group) {
      $vars['exists'] = Group::groupExists($group);
      $vars['group'] = $group = Group::load($group);
      $users = [];
      foreach (UsersManager::$instance->users() as $u) {
        if (in_array($group['groupname'], $u->get('groups', []))) {
          $users[] = $u->username;
        }
      }
      $group['users'] = $users;
    } else {
      $vars['fields'] = $this->plugin->getModalsConfiguration()['add_group']['fields'];
      $vars['bulkFields'] = $this->plugin->getModalsConfiguration()['bulk_group']['fields'];

      $groups = $this->groups();
      foreach ($groups as &$group) {
        $group['users'] = 0;

        foreach (UsersManager::$instance->users() as $u) {
          if (in_array($group['groupname'], $u->get('groups', []))) {
            $group['users'] += 1;
          }
        }
      }

      // Filtering
      $filterException = false;
      $filter = (empty($_GET['filter'])) ? '' : $_GET['filter'];
      $vars['filter'] = $filter;
      if ($filter) {
        try {
          $language = new ExpressionLanguage();
          $language->addFunction(ExpressionFunction::fromPhp('count'));
          foreach ($groups as $k => $group) {
            if (!$language->evaluate($_GET['filter'], ['group' => $group])) {
              unset($groups[$k]);
            }
          }
        } catch (\Exception $exception) {
          $vars['filterException'] = $exception;
          $filterException = true;
        }
      }

      if ($filterException) {
        $groups = [];
      }

      // Pagination
      $perPage = $this->plugin->getPluginConfigValue('pagination.per_page', 10);
      $pagination = new ArrayPagination($groups, $perPage);
      $pagination->paginate($uri->param('page'));

      $vars['pagination'] = [
        'current' => $pagination->getCurrentPage(),
        'count' => $pagination->getPagesCount(),
        'total' => $pagination->getRowsCount(),
        'perPage' => $pagination->getRowsPerPage(),
        'startOffset' => $pagination->getStartOffset(),
        'endOffset' => $pagination->getEndOffset()
      ];
      $groups = $pagination->getPaginatedRows();

      $vars['groups'] = $groups;
    }

    return $vars;
  }

  public function groups() {
    return Group::groups();
  }

}
