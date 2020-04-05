<?php
namespace AdminAddonUserManager\Users;

use Grav\Common\Grav;
use Grav\Plugin\AdminAddonUserManagerPlugin;
use Grav\Common\Assets;
use RocketTheme\Toolbox\Event\Event;
use AdminAddonUserManager\Manager as IManager;
use Grav\Common\User\User;
use Grav\Common\Data\Blueprints;

class ExpertManager implements IManager {

  private $grav;
  private $plugin;

  public static $instance;

  public function __construct(Grav $grav, AdminAddonUserManagerPlugin $plugin) {
    $this->grav = $grav;
    $this->plugin = $plugin;

    self::$instance = $this;
  }

  /**
   * Returns the required permission to access the manager
   *
   * @return string
   */
  public function getRequiredPermission() {
    return $this->plugin->name . '.users_expert';
  }

  /**
   * Returns the location of the manager
   * It will be accessible at this path
   *
   * @return string
   */
  public function getLocation() {
    return 'user-expert';
  }

  /**
   * Returns the plugin hooked nav array
   *
   * @return array
   */
  public function getNav() {
    return false;
  }

  /**
   * Initialiaze required assets
   *
   * @param \Grav\Common\Assets $assets
   * @return void
   */
  public function initializeAssets(Assets $assets) {
    $assets->addCss('plugin://admin/themes/grav/css/codemirror/codemirror.css');
  }

  /**
   * Handle task requests
   *
   * @param \RocketTheme\Toolbox\Event\Event $event
   * @return boolean
   */
  public function handleTask(Event $event) {}

  /**
   * Logic of the manager goes here
   *
   * @return array The array to be merged to Twig vars
   */
  public function handleRequest() {
    $vars = [];

    $twig = $this->grav['twig'];
    $uri = $this->grav['uri'];

    $username = $uri->paths()[2];
    $user = $this->grav['accounts']->load($username);

    if (isset($_POST['raw'])) {
      $user->file()->raw($_POST['raw']);
      $user->file()->save();
    }

    $vars['raw'] = $user->file()->raw();
    $vars['user'] = $user;

    $blueprints = new Blueprints;
    $vars['blueprint'] = $blueprints->get('user/account-raw');

    return $vars;
  }

}