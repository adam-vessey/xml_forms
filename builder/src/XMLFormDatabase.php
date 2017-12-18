<?php

namespace Drupal\xml_form_builder;

use Drupal\xml_form_api\XMLFormDefinition;

use DOMDocument;

/**
 * Provides a wrapper class around the database table were forms are stored.
 */
class XMLFormDatabase {

  /**
   * Constants.
   */
  const TABLE = 'xml_forms';

  // @deprecated Constants
  // @codingStandardsIgnoreStart
  const table = self::TABLE;
  // @codingStandardsIgnoreEnd

  /**
   * Checks to see if the given form exists in the database.
   *
   * @param string $form_name
   *   The name of the XML Form Definition.
   *
   * @return bool
   *   TRUE if the given form exists, FALSE otherwise.
   */
  public static function exists($form_name) {
    // @TODO: integrate 'fancy' 'dynamic' self::table
    $count = db_select('xml_forms', 'xf')
      ->condition('xf.name', $form_name)
      ->fields('xf', ['name'])
      ->countQuery()
      ->execute()
      ->fetchField();

    return $count == 1;
  }

  /**
   * Checks to see if the given form exists in the database and is valid.
   *
   * @param string $form_name
   *   The name of the XML Form Definition.
   *
   * @return bool
   *   TRUE if the given form exists, FALSE otherwise.
   */
  public static function valid($form_name) {
    if (static::exists($form_name)) {
      return static::get($form_name) !== FALSE;
    }
    return FALSE;
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
    if (static::exists($form_name)) {
      $query = db_query('SELECT form FROM {xml_forms} WHERE name = :name', [':name' => $form_name]);
      $xml = $query->fetchField();
      if (trim($xml) !== '') {
        $definition = new DOMDocument();
        $definition->loadXML($xml);
        $definition = static::upgrade($form_name, $definition);
        return $definition;
      }
    }
    return FALSE;
  }

  /**
   * Upgrade to the latest version and save it back to the database.
   *
   * @param string $form_name
   *   The name of the form to update.
   * @param DOMDocument $definition
   *   The form definition to update.
   *
   * @return XMLFormDefinition
   *   The form definition, upgraded to the latest version.
   */
  protected static function upgrade($form_name, DOMDocument $definition) {
    module_load_include('inc', 'xml_form_api', 'XMLFormDefinition');
    $version = XMLFormDefinition::getVersion($definition);
    if (!$version->isLatestVersion()) {
      $definition = XMLFormDefinition::upgradeToLatestVersion($definition);
      // Updates to latest.
      static::update($form_name, $definition);
    }
    return $definition;
  }

  /**
   * Gets a list of all defined form names.
   *
   * @return array
   *   An array of defined form names, where both the key and the value are the
   *   form's name; e.g. array('name' => 'name').
   */
  public static function getNames() {
    $names = [];
    $result = db_query('SELECT name FROM {xml_forms}')->fetchCol();
    foreach ($result as $data) {
      $names[] = ['name' => $data, 'indb' => TRUE];
    }
    return $names;
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
    foreach ($form_names as $key => $form_name) {
      if (static::valid($form_name)) {
        $valid_names[$key] = $form_name;
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
   *   An XML Form Definition.
   *
   * @return bool
   *   TRUE if successful, otherwise FALSE.
   */
  public static function create($form_name, DOMDocument $definition = NULL) {
    if (!static::exists($form_name)) {
      module_load_include('inc', 'xml_form_api', 'XMLFormDefinition');
      $definition = isset($definition) ? $definition : xml_form_api_get_empty_form_definition();
      $definition = XMLFormDefinition::upgradeToLatestVersion($definition);
      $fields = [];
      $fields['name'] = $form_name;
      $fields['form'] = $definition->saveXML();
      return \Drupal::database()->insert(static::TABLE)->fields($fields)->execute() !== FALSE;
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
   *   TRUE if successful; FALSE otherwise.
   */
  public static function copy($form_name_src, $form_name_dest) {
    if (static::exists($form_name_src)) {
      $definition = static::get($form_name_src);
      return static::create($form_name_dest, $definition);
    }
    return FALSE;
  }

  /**
   * Updates the form with the given form name; the previous definition is lost.
   *
   * If the form does not exist, this function will fail.
   *
   * @param string $form_name
   *   The name of the XML Form Definition.
   * @param DOMDocument $definition
   *   A XML Form Definition.
   *
   * @returns bool
   *   TRUE if successful; FALSE otherwise.
   */
  public static function update($form_name, DOMDocument $definition) {
    if (static::exists($form_name)) {
      $version = XMLFormDefinition::getVersion($definition);
      if (!$version->isLatestVersion()) {
        $definition = XMLFormDefinition::upgradeToLatestVersion($definition);
      }
      $num = db_update(static::TABLE)
        ->fields(['form' => $definition->saveXML()])
        ->condition('name', $form_name)
        ->execute();
      if ($num) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Deletes the form with the given form name.
   *
   * @param string $form_name
   *   The name of the XML Form Definition.
   *
   * @return bool
   *   TRUE if successful; FALSE otherwise.
   */
  public static function delete($form_name) {
    if (static::exists($form_name)) {
      $num = db_delete(static::TABLE)
        ->condition('name', $form_name)
        ->execute();
      if ($num) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    return FALSE;
  }

}
