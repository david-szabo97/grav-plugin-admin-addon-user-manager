<?php
// Copied from Grav source because it has issues which has been fixed here

namespace AdminAddonUserManager;

use Grav\Common\Data\Blueprints;
use Grav\Common\Data\Data;
use Grav\Common\File\CompiledYamlFile;
use Grav\Common\Grav;
use Grav\Common\Utils;

class Group extends Data {

  /**
   * Get the groups list
   *
   * @return array
   */
  public static function groups() {
    $groups = Grav::instance()['config']->get('groups', []);

    $blueprints = new Blueprints;
    $blueprint = $blueprints->get('user/group');
    foreach ($groups as $groupname => &$content) {
      if (!isset($content['groupname'])) {
        $content['groupname'] = $groupname;
      }
      $content = new Group($content, $blueprint);
    }

    return $groups;
  }

  /**
   * Get the groups list
   *
   * @return array
   */
  public static function groupNames() {
    $groups = [];

    foreach(Grav::instance()['config']->get('groups', []) as $groupname => $group) {
      $groups[$groupname] = isset($group['readableName']) ? $group['readableName'] : $groupname;
    }

    return $groups;
  }

  /**
   * Checks if a group exists
   *
   * @param string $groupname
   *
   * @return bool
   */
  public static function groupExists($groupname) {
    return isset(self::groups()[$groupname]);
  }

  /**
   * Get a group by name
   *
   * @param string $groupname
   *
   * @return object
   */
  public static function load($groupname) {
    if (self::groupExists($groupname)) {
      $group = self::groups()[$groupname];
    } else {
      $blueprints = new Blueprints;
      $blueprint = $blueprints->get('user/group');
      $content = ['groupname' => $groupname];
      $group = new Group($content, $blueprint);
    }

    return $group;
  }

  /**
   * Save a group
   */
  public function save() {
    $grav = Grav::instance();
    $config = $grav['config'];

    $blueprints = new Blueprints;
    $blueprint = $blueprints->get('user/group');

    $fields = $blueprint->fields();

    $config->set("groups.$this->groupname", []);

    foreach ($fields as $field) {
      if ($field['type'] == 'text') {
        $value = $field['name'];
        if (isset($this->items[$value])) {
          $config->set("groups.$this->groupname.$value", $this->items[$value]);
        }
      }

      if ($field['type'] == 'array' || $field['type'] == 'permissions') {
        $value = $field['name'];
        $arrayValues = Utils::getDotNotation($this->items, $field['name']);

        if ($arrayValues) {
          foreach ($arrayValues as $arrayIndex => $arrayValue) {
            $config->set("groups.$this->groupname.$value.$arrayIndex", $arrayValue);
          }
        }
      }
    }

    $type = 'groups';
    $obj = new Data($config->get($type), $blueprint);
    $file = CompiledYamlFile::instance($grav['locator']->findResource('config://') . DS . "{$type}.yaml");
    $obj->file($file);
    $obj->save();
  }

  /**
   * Remove a group
   *
   * @param string $groupname
   *
   * @return bool True if the action was performed
   */
  public static function remove($groupname) {
    $grav = Grav::instance();
    $config = $grav['config'];
    $blueprints = new Blueprints;
    $blueprint = $blueprints->get('user/group');

    $groups = $config->get('groups', []);
    if (!isset($groups[$groupname])) {
      return false;
    }
    unset($groups[$groupname]);
    $config->set('groups', $groups);

    $type = 'groups';
    $obj = new Data($config->get($type), $blueprint);
    $file = CompiledYamlFile::instance($grav['locator']->findResource("config://{$type}.yaml"));
    $obj->file($file);
    $obj->save();

    return true;
  }

  public function authorize($access) {
    if (empty($this->items)) {
      return false;
    }

    if (!isset($this->items['access'])) {
      return false;
    }

    $val = Utils::getDotNotation($this->items['access'], $access);

    return Utils::isPositive($val) === true;
  }

}