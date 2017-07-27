<?php
namespace AdminAddonUserManager;

use Grav\Common\Grav;
use Grav\Plugin\AdminAddonUserManagerPlugin;
use Grav\Common\Assets;
use RocketTheme\Toolbox\Event\Event;

interface Manager {

  public function __construct(Grav $grav, AdminAddonUserManagerPlugin $plugin);

  /**
   * Returns the required permission to access the manager
   *
   * @return string
   */
  public function getRequiredPermission();

  /**
   * Returns the location of the manager
   * It will be accessible at this path
   *
   * @return string
   */
  public function getLocation();

  /**
   * Returns the plugin hooked nav array
   *
   * @return array
   */
  public function getNav();

  /**
   * Initialiaze required assets
   *
   * @param \Grav\Common\Assets $assets
   * @return void
   */
  public function initializeAssets(Assets $assets);

  /**
   * Handle task requests
   *
   * @param \RocketTheme\Toolbox\Event\Event $event
   * @return void
   */
  public function handleTask(Event $event);

  /**
   * Logic of the manager goes here
   *
   * @return array The array to be merged to Twig vars
   */
  public function handleRequest();

}