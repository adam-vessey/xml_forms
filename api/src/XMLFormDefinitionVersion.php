<?php

namespace Drupal\xml_form_api;

use DOMDocument;
use XSLTProcessor;

/**
 * The version number and related transforms/schemas of an XMLFormDefinition.
 */
class XMLFormDefinitionVersion {

  /**
   * The version which this class represents.
   *
   * @var int
   */
  protected $version;

  /**
   * The path to the directory where all the schema definitions are stored.
   *
   * @return string
   *   What it says on the tin.
   */
  public static function getPathToSchemaDefinitionDirectory() {
    return drupal_get_path('module', 'xml_form_api') . '/data/schemas';
  }

  /**
   * The path to the directory where all the XSL Transformations are stored.
   *
   * @return string
   *   Exactly what you'd expect.
   */
  public static function getPathToXSLTransformDirectory() {
    return drupal_get_path('module', 'xml_form_api') . '/data/transforms';
  }

  /**
   * Gets a list of all the known versions of a XML Form Definition.
   *
   * @return array
   *   An array where all the values correspond to the defined versions of an
   *   XML Form Definition, in order from oldest to most recent.
   */
  public static function getAllVersions() {
    static $versions = NULL;
    if (empty($versions)) {
      $versions = [];
      $path = self::getPathToSchemaDefinitionDirectory();
      $files = scandir($path);
      foreach ($files as $filename) {
        if (preg_match('/^.*\.xsd$/', $filename)) {
          $version = str_replace('.xsd', '', $filename);
          $versions[] = (int) $version;
        }
      }
      asort($versions);
    }
    return $versions;
  }

  /**
   * Checks to see if the given version number is valid.
   *
   * Valid schema numbers are defined by having a corresponding Schema file with
   * the same version.
   *
   * @param int $version
   *   A XML Form Definition version number.
   *
   * @return bool
   *   TRUE if the version is valid; FALSE otherwise.
   */
  public static function isValid($version) {
    $versions = self::getAllVersions();
    return array_search($version, $versions) !== FALSE;
  }

  /**
   * Gets the latest schema version number.
   *
   * @return int
   *   The latest version number.
   */
  public static function getLatestVersion() {
    $versions = self::getAllVersions();
    $latest_version = array_pop($versions);
    return new XMLFormDefinitionVersion($latest_version);
  }

  /**
   * Gets the version number of the XML Form Definition.
   *
   * Also validates the document against that version number.
   *
   * @param DOMDocument $definition
   *   The XML Form Definition.
   *
   * @return float|bool
   *   The version of the XML Form Definition, or FALSE if invalid.
   */
  public static function getVersion(DOMDocument $definition) {
    $declares_version = $definition->documentElement->hasAttribute('version');
    if ($declares_version) {
      $version = (int) $definition->documentElement->getAttribute('version');
      return self::isValid($definition, $version);
    }
    else {
      // Files with a version of 0 or 1 may not have their version declared.
      // This checks manually to confirm in these cases.
      $undeclared_versions = [
        new XMLFormDefinitionVersion(0),
        new XMLFormDefinitionVersion(1),
      ];
      foreach ($undeclared_versions as $version) {
        if (self::isValid($definition, $version)) {
          return $version;
        }
      }
    }
    // Could not find the version.
    return FALSE;
  }

  /**
   * Creates an instance of the XMLFormDefinitionVersion.
   *
   * @param int $version
   *   The XML Form Definition Schema number.
   */
  public function __construct($version) {
    module_load_include('inc', 'php_lib', 'String');
    module_load_include('inc', 'php_lib', 'Array');

    if (self::isValid($version)) {
      $this->version = $version;
    }
  }

  /**
   * Gets the numerical representation of this version.
   *
   * @return int
   *   The version number.
   */
  public function get() {
    return $this->version;
  }

  /**
   * Gets the next most recent version number from the version number given.
   *
   * @return XMLFormDefinitionVersion
   *   The next version number if given is not the latest; FALSE otherwise.
   */
  public function getNextVersion() {
    $versions = self::getAllVersions();
    $position = array_search($this->get(), $versions);
    $next_position = ++$position;
    if (isset($versions[$next_position])) {
      $next_version = $versions[$next_position];
      return new XMLFormDefinitionVersion($next_version);
    }
    return FALSE;
  }

  /**
   * Checks to see if the XML Form Definition is at the most recent version.
   *
   * @return bool
   *   TRUE if it is the latest, FALSE otherwise.
   */
  public function isLatestVersion() {
    $latest = self::getLatestVersion();
    return $this->get() == $latest->get();
  }

  /**
   * Gets the filename of the Schema that represents this version.
   *
   * @return string
   *   The schema filename.
   */
  public function getSchemaFileName() {
    $path = self::getPathToSchemaDefinitionDirectory();
    return $path . '/' . $this->version . '.xsd';
  }

  /**
   * Gets the filename of the XSL Transform that represents this version.
   *
   * @return string
   *   The transform filename.
   */
  public function getTransformFileName() {
    $path = self::getPathToXSLTransformDirectory();
    return $path . '/' . $this->version . '.xsl';
  }

  /**
   * Gets an XSLTProcessor.
   *
   * The XSLTProcessor uses XMLFormDefinition::getTransformFileName(), which
   * requires XMLFormDefinition::version to be set.
   *
   * @return bool|XSLTProcessor
   *   The appropriate XSLTProcessor, or FALSE if the transform doesn't exist.
   */
  public function getTransform() {
    $filename = $this->getTransformFileName();
    if (file_exists($filename)) {
      $xsl = new DOMDocument();
      $xsl->load($filename);
      $xslt = new XSLTProcessor();
      $xslt->importStyleSheet($xsl);
      return $xslt;
    }
    return FALSE;
  }

}
