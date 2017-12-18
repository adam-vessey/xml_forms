<?php

namespace Drupal\xml_form_builder;

use Drupal\xml_form_api\XMLFormDefinition;

use DOMDocument;

/**
 * Provides a wrapper class around getting forms from the database or hooks.
 */
class XMLFormRepository extends XMLFormDatabase {

  /**
   * Returns forms defined by hooks in modules.
   *
   * @return array[]
   *   An array where the keys are the form names, paired with values which are
   *   also arrays, in the format "array('form_file' => 'path/to/the/form')".
   */
  protected static function getFormsFromHook() {
    $hooks = \Drupal::moduleHandler()->invokeAll('xml_form_builder_forms');
    // @todo Remove (deprecated) invokation if
    // "islandora_xml_form_builder_forms".
    $hooks += \Drupal::moduleHandler()->invokeAll('islandora_xml_form_builder_forms');
    return $hooks;
  }

  /**
   * Checks to see if the given form exists.
   *
   * @param string $form_name
   *   The name of the XML Form Definition.
   *
   * @return bool
   *   TRUE if the given form exists, FALSE otherwise.
   */
  public static function exists($form_name) {
    $in_database = parent::exists($form_name);

    if ($in_database) {
      return TRUE;
    }

    $forms = static::getFormsFromHook();
    if (isset($forms[$form_name])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks to see if the given form exists and is a valid definition.
   *
   * @param string $form_name
   *   The name of the XML Form Definition.
   *
   * @return bool
   *   TRUE if the given form exists, FALSE otherwise.
   */
  public static function valid($form_name) {
    $in_database = parent::exists($form_name);

    if ($in_database) {
      return parent::valid($form_name);
    }

    return static::get($form_name) !== FALSE;
  }

  /**
   * Gets the XML Form Definition identified by name.
   *
   * @param string $form_name
   *   The name of the XML Form Definition.
   *
   * @return DOMDocument
   *   The XML Form Definition if found, FALSE otherwise.
   */
  public static function get($form_name) {
    $in_database = parent::exists($form_name);
    if ($in_database) {
      return parent::get($form_name);
    }

    $hooks = static::getFormsFromHook();
    if (!isset($hooks[$form_name])) {
      return FALSE;
    }

    $path = $hooks[$form_name]['form_file'];
    if (!file_exists($path)) {
      return FALSE;
    }

    $definition = new DOMDocument();
    $definition->load($path);
    $version = XMLFormDefinition::getVersion($definition);
    if (!$version->isLatestVersion()) {
      $definition = XMLFormDefinition::upgradeToLatestVersion($definition);
    }
    return $definition;
  }

  /**
   * Gets a list of all defined form names.
   *
   * @return array
   *   An array of defined form names, where both the key and the value are the
   *   form's name, e.g. array('name' => 'name').
   */
  public static function getNames() {
    $hook = static::getFormsFromHook();
    $hook_names = [];
    foreach ($hook as $key => $array) {
      $hook_names[] = ['name' => $key, 'indb' => FALSE];
    }
    usort($hook_names, [__CLASS__, 'comparisonFunction']);

    $db_names = parent::getNames();
    usort($hook_names, [__CLASS__, 'comparisonFunction']);

    $names = array_merge($hook_names, $db_names);

    return $names;
  }

  /**
   * Compares the strings inside the 'name' key for two arrays.
   *
   * @param array $a
   *   The first array to use in the comparison; must contain a 'name' key.
   * @param array $b
   *   The second array to use in the comparison; must contain a 'name' key.
   *
   * @return int
   *   The string comparison as a strnatcasecmp() integer.
   */
  public static function comparisonFunction($a, $b) {
    return strnatcasecmp($a['name'], $b['name']);
  }

  /**
   * Gets a list of all defined form names that have valid definitions.
   *
   * @return array
   *   An array of defined form names, where both the key and the value are the
   *   form's name, e.g. array('name' => 'name').
   */
  public static function getValidNames() {
    $form_names = static::getNames();
    $valid_names = [];
    foreach ($form_names as $form_name) {
      if (static::valid($form_name['name'])) {
        $valid_names[] = $form_name;
      }
    }
    return $valid_names;
  }

  /**
   * Creates a form with the given form name and definition.
   *
   * If the form already exists it will fail.
   *
   * @param string $form_name
   *   The name of the XML Form Definition.
   * @param DOMDocument $definition
   *   A XML Form Definition.
   *
   * @return bool
   *   TRUE if successful, otherwise FALSE.
   */
  public static function create($form_name, DOMDocument $definition = NULL) {
    if (!static::exists($form_name)) {
      return parent::create($form_name, $definition);
    }
    return FALSE;
  }

  /**
   * Copies an existing form.
   *
   * @param string $form_name_src
   *   The name of the source form to copy from.
   * @param string $form_name_dest
   *   The name of the destination form which gets copied to.
   *
   * @return bool
   *   TRUE if successful FALSE otherwise.
   */
  public static function copy($form_name_src, $form_name_dest) {
    if (static::exists($form_name_src)) {
      $definition = static::get($form_name_src);
      return static::create($form_name_dest, $definition);
    }
    return FALSE;
  }

}
