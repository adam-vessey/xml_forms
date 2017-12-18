<?php

namespace Drupal\xml_form_api;

use Drupal\objective_forms\FormElement;
use Drupal\objective_forms\FormPropertyInterface;

/**
 * Creates an XML Form Definition from a Drupal form and an array of properties.
 */
class XMLFormDefinitionGenerator {

  /**
   * Creates a DOMDocument that defines an XML Form.
   *
   * @param array $properties
   *   The form properties.
   * @param array $form
   *   A Drupal form.
   *
   * @return DOMDocument
   *   An XML Form Definition.
   */
  public static function create(array &$properties, array &$form) {
    $latest = XMLFormDefinitionVersion::getLatestVersion();
    $definition = new SimpleXMLElement("<definition xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' version='{$latest->get()}'/>");
    static::addProperties($definition, $properties);
    // @todo no registry defined...
    static::addElement($definition->addChild('form'), new FormElement(NULL, $form));
    $xml = $definition->asXML();
    $definition = new DOMDocument();
    $definition->loadXML($xml);
    return $definition;
  }

  /**
   * Adds the properties to the root element of the XML Form Definition.
   *
   * @param SimpleXMLElement $definition
   *   The root element of the XML Form Definition.
   * @param array $properties
   *   The form properties.
   */
  protected static function addProperties(SimpleXMLElement $definition, array &$properties) {
    $form_properties = $definition->addChild('properties');
    if (isset($properties['root_name'])) {
      $form_properties->addChild('root_name', $properties['root_name']);
    }
    if (isset($properties['schema_uri'])) {
      $form_properties->addChild('schema_uri', $properties['schema_uri']);
    }
    $namespaces = $form_properties->addChild('namespaces');
    if ($properties['default_uri']) {
      $namespaces->addAttribute('default', $properties['default_uri']);
    }
    if (isset($properties['namespaces'])) {
      foreach ($properties['namespaces'] as $prefix => $uri) {
        $namespace = $namespaces->addChild('namespace', $uri);
        $namespace->addAttribute('prefix', $prefix);
      }
    }
  }

  /**
   * Adds the form to a parent element of the XML Form Definition.
   *
   * @param SimpleXMLElement $parent
   *   The parent element to add the new element to.
   * @param \Drupal\objective_forms\FormElement $element
   *   The element to add.
   */
  protected static function addElement(SimpleXMLElement $parent, FormElement $element) {
    $properties = $parent->addChild('properties');
    foreach ($element->controls as $key => $value) {
      static::addElementProperty($properties, $key, $value);
    }
    $children = $parent->addChild('children');
    foreach ($element->children as $key => $child) {
      $element = $children->addChild('element');
      $element->addAttribute('name', $key);
      static::addElement($element, $child);
    }
  }

  /**
   * Adds an element property to the properties of an element declaration.
   *
   * @param SimpleXMLElement $properties
   *   The properties section of an element declaration.
   * @param string $key
   *   The property's name.
   * @param mixed $value
   *   The property's value.
   */
  protected static function addElementProperty(SimpleXMLElement $properties, $key, $value) {
    $property = static::createElementProperty($properties, $key);
    static::setElementProperty($property, $value);
  }

  /**
   * Creates an XML Tag representation of a property.
   *
   * @param SimpleXMLElement $properties
   *   The parent which the new property will belong to.
   * @param string $key
   *   The potential tag name for the newly-created property.
   *
   * @return SimpleXMLElement
   *   The created Element Property.
   */
  protected static function createElementProperty(SimpleXMLElement $properties, $key) {
    $key = trim($key, '#');
    if (!static::isValidXMLTag($key)) {
      $property = $properties->addChild('index');
      $property->addAttribute('key', cast_type_to_string($key));
      return $property;
    }
    return $properties->addChild($key);
  }

  /**
   * Sets the Element Property.
   *
   * @param SimpleXMLElement $property
   *   The property whose value will be set.
   * @param mixed $value
   *   The value to assign to the $property.
   */
  protected static function setElementProperty(SimpleXMLElement $property, $value) {
    $value = ($value instanceof FormPropertyInterface) ? $value->toDrupalForm() : $value;
    if (is_array($value)) {
      foreach ($value as $key => $item) {
        static::addElementProperty($property, $key, $item);
      }
    }
    else {
      $property[0] = cast_type_to_string($value);
    }
  }

  /**
   * Checks to see if the given tag name can be used for an XML tag.
   *
   * Numbers are invalid XML tags.
   *
   * @param string $tag_name
   *   The proposed tag name.
   *
   * @return bool
   *   TRUE if the name is valid FALSE otherwise.
   */
  protected static function isValidXmlTag($tag_name) {
    // Start [A-Z] | "_" | [a-z].
    // Everything else is start plus | "-" | "." | [0-9].
    return (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tag_name) > 0) ? TRUE : FALSE;
  }

}
