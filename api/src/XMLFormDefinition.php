<?php

namespace Drupal\xml_form_api;

use Drupal\objective_forms\FormElement;

use DOMDocument;
use DOMXPath;
use SimpleXMLElement;

/**
 * Validates and transforms XML Form Definitions into Drupal form arrays.
 */
class XMLFormDefinition {

  /**
   * The DOMDocument that represents the Form Definition.
   *
   * @var DOMDocument
   */
  public $definition;

  /**
   * Gets the version number of the XML Form Definition.
   *
   * Also validates the document against that version number.
   *
   * @throws Exception
   *  If the Version can not be determined or if the definition is not valid.
   *
   * @param DOMDocument $definition
   *   The XML Form Definition.
   *
   * @return float
   *   The version of the XML Form Definition, or FALSE if invalid.
   */
  public static function getVersion(DOMDocument $definition) {
    if (empty($definition->documentElement)) {
      throw new Exception(t('XML form definition is not valid.'));
    }
    $declares_version = $definition->documentElement->hasAttribute('version');
    if ($declares_version) {
      $version = (int) $definition->documentElement->getAttribute('version');
      $version = new XMLFormDefinitionVersion($version);
      if (self::isValid($definition, $version)) {
        return $version;
      }
      throw new Exception(t('XML form definition is not valid.'));
    }
    else {
      // Files with a version of 0 or 1 may not have their version declared.
      // Check manually to see if this is the case.
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
    throw new Exception(t('Failed to determine the version of the XML form definition'));
  }

  /**
   * Uses the XML Form Definition version's schema to validate the definition.
   *
   * @param DOMDocument $definition
   *   The XML Form Definition.
   * @param XMLFormDefinitionVersion $version
   *   The schema version, if none is give the latest is used.
   *
   * @return bool
   *   TRUE if the XML Form Definition is valid, FALSE otherwise.
   */
  public static function isValid(DOMDocument $definition, XMLFormDefinitionVersion $version = NULL) {
    $version = isset($version) ? $version : XMLFormDefinitionVersion::getLatestVersion();
    $file_name = $version->getSchemaFileName();
    return @$definition->schemaValidate($file_name);
  }

  /**
   * Upgrades the XML Form Definition to the next available version.
   *
   * If the document is already at its latest version nothing occurs.
   *
   * @param DOMDocument $definition
   *   The XML Form Definition to upgrade. May be modified by this function.
   *
   * @return XMLFormDefinitionVersion
   *   The upgraded version of the XML Form Definition.
   */
  public static function upgradeVersion(DOMDocument &$definition) {
    $current = self::getVersion($definition);
    $next = $current->getNextVersion();
    if ($next) {
      // There exists a version to upgrade to.
      $xslt = $next->getTransform();
      $definition = $xslt->transformToDoc($definition->documentElement);
      return $next;
    }
    // Do nothing.
    return $current;
  }

  /**
   * Repeatedly upgrades the XML Form Definition till it reaches the latest.
   *
   * @param DOMDocument $definition
   *   The XML Form Definition to upgrade.
   *
   * @return XMLFormDefinition
   *   The upgraded XML Form Definition.
   */
  public static function upgradeToLatestVersion(DOMDocument $definition) {
    do {
      $version = self::upgradeVersion($definition);
    } while (!$version->isLatestVersion());
    return $definition;
  }

  /**
   * Creates an instance of the XMLFormDefinition.
   *
   * @param DOMDocument $definition
   *   The form definition to construct.
   *
   * @throws Exception
   *   If the form definition is invalid.
   */
  public function __construct(DOMDocument $definition) {
    $this->definition = self::upgradeToLatestVersion($definition);
    if (!self::isValid($this->definition)) {
      throw new Exception('Unable to create XMLFormDefinition.');
    }
  }

  /**
   * Gets the definition.
   *
   * @return DOMDocument
   *   The form definition.
   */
  public function get() {
    return $this->definition;
  }

  /**
   * Extracts the form section of the definition into a array.
   *
   * @return array
   *   The extracted form declaration from the XML Form Definition.
   *   This is also a valid Drupal form.
   */
  public function getForm() {
    $definition = simplexml_import_dom($this->definition);
    return $this->getElement($definition->form);
  }

  /**
   * Creates an XMLDocument instance from the XML Form Definition's properties.
   *
   * @param string $xml
   *   The metadata to initialize the XMLDocument with.
   *
   * @return XMLDocument
   *   An initialized XMLDocument.
   */
  public function createXMLDocument($xml = NULL) {
    $properties = $this->getProperties();
    // @todo change to name.
    $root_name = $properties['root_name'];
    $schema_uri = isset($properties['schema_uri']) ? $properties['schema_uri'] : NULL;
    $default_uri = isset($properties['default_uri']) ? $properties['default_uri'] : NULL;
    $namespaces = new Namespaces($properties['namespaces'], $default_uri);
    return new XMLDocument($root_name, $namespaces, $schema_uri, $xml);
  }

  /**
   * Extracts the properties section of the definition into a array.
   *
   * @return array
   *   The extracted properties from the XML Form Definition.
   */
  public function getProperties() {
    $paths = [
      'root_name' => '/definition/properties/root_name',
      'default_uri' => '/definition/properties/namespaces/@default',
      'schema_uri' => '/definition/properties/schema_uri',
    ];
    $properties = [];
    $xpath = new DOMXPath($this->definition);
    foreach ($paths as $key => $path) {
      $results = $xpath->query($path);
      if ($results->length) {
        $properties[$key] = $results->item(0)->nodeValue;
      }
    }
    $results = $xpath->query('/definition/properties/namespaces/namespace');
    $count = $results->length;
    for ($i = 0; $i < $count; $i++) {
      $node = $results->item($i);
      $prefix = $node->getAttribute('prefix');
      $properties['namespaces'][$prefix] = $node->nodeValue;
    }
    return $properties;
  }

  /**
   * Gets the array representation of an Element from the XML Form Definition.
   *
   * @param SimpleXMLElement $element
   *   The element declaration to transform into its Drupal form.
   *
   * @return array
   *   A Drupal form equivalent of the XML Form Definition element declaration.
   */
  protected function getElement(SimpleXMLElement $element) {
    $properties = $this->getElementProperties($element);
    return array_merge($properties, $this->getElementChildren($element));
  }

  /**
   * Transforms an element declaration's properties to Drupal form equivalent.
   *
   * @param SimpleXMLElement $element
   *   An element declaration from the XML Form Definition.
   *
   * @return array
   *   The Drupal representation of this element's properties declaration.
   */
  protected function getElementProperties(SimpleXMLElement $element) {
    $output = [];
    if (isset($element->properties)) {
      $properties = $element->properties->children();
      foreach ($properties as $property) {
        $name = static::getElementPropertyName($property);
        $output["#$name"] = $this->getElementProperty($property);
      }
    }
    return $output;
  }

  /**
   * Gets the value of an XML Form Definition property.
   *
   * This is found within the properties section of an element declaration.
   *
   * @param SimpleXMLElement $property
   *   An element property declaration from the XML Form Definition.
   *
   * @return mixed
   *   The value of the property.
   */
  protected function getElementProperty(SimpleXMLElement $property) {
    module_load_include('inc', 'php_lib', 'String');

    $children = $property->children();
    if (count($children) == 0) {
      // If cast fails the string is returned.
      $type = cast_string_to_type((string) $property);
      // XXX: This is to handle the renaming of the datepicker element for built
      // in forms. Remove this at some point.
      return $type === 'datepicker' ? XML_FORM_ELEMENTS_DATEPICKER_THEME : $type;
    }
    $output = [];
    foreach ($children as $child) {
      $name = cast_string_to_type((string) $this->getElementPropertyName($child));
      $output[$name] = $this->getElementProperty($child);
    }
    return $output;
  }

  /**
   * Gets the name to use as the array index of the property in the Drupal form.
   *
   * @param SimpleXMLElement $property
   *   An element property declaration from the XML Form Definition.
   *
   * @return string
   *   The property name.
   */
  protected function getElementPropertyName(SimpleXMLElement $property) {
    if (isset($property['key'])) {
      $key = (string) $property['key'];
      return strcasecmp($key, 'NULL') == 0 ? NULL : $key;
    }
    return $property->getName();
  }

  /**
   * Transforms all child declarations of an element to their Drupal equivalent.
   *
   * @param SimpleXMLElement $element
   *   The element declaration to transform into its Drupal form equivalent.
   *
   * @return array
   *   All the transformed child declarations.
   */
  protected function getElementChildren(SimpleXMLElement $element) {
    module_load_include('inc', 'php_lib', 'Array');
    $output = [];
    if (isset($element->children)) {
      $elements = $element->children->children();
      foreach ($elements as $element) {
        $key = isset($element['name']) ? (string) $element['name'] : NULL;
        array_add($output, $key, $this->getElement($element));
      }
    }
    dsm($output, 'output');
    return $output;
  }

}
