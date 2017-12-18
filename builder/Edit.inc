<?php

/**
 * @file
 * Callbacks and functions used in the Edit Form Page.
 *
 * These are used for rendering the Form Builder GUI.
 */

use Drupal\xml_form_api\XMLFormDefinition;
use Drupal\xml_form_builder\XMLFormDatabase;

/**
 * Includes all the required CSS files needed to render the Form Builder GUI.
 */
function xml_form_builder_edit_include_css() {
  return [
    '#attached' => [
      'library' => ['xml_form_builder/xml-form-builder-css'],
    ],
  ];
}

/**
 * Includes all the required JS files needed to render the Form Builder GUI.
 */
function xml_form_builder_edit_include_js() {
  return [
    '#attached' => [
      'library' => ['xml_form_builder/xml-form-builder-js'],
    ],
  ];
}

/**
 * Creates an ExtJS store containing all the possible form field types.
 *
 * Uses inline javascript to create the ExtJS store.
 *
 * Used in the Element Type Combo Box, in the Element form of the Form Builder
 * GUI.
 */
function xml_form_builder_create_element_type_store() {
  $types = xml_form_builder_get_element_types();
  $data = [];
  foreach ($types as $type) {
    $data[] = [
      'display' => $type,
      'value' => $type,
    ];
  }
  return [
    '#attached' => [
      'library' => ['xml_form_builder/xml-form-builder-element-types-js'],
      'drupalSettings' => [
        'xml_form_builder' => ['element_types' => $data],
      ],
    ],
  ];
}

/**
 * Creates an ExtJS store containing the form properties.
 *
 * Used to populate the 'Form Properties' Form.
 *
 * @param string $form_name
 *   The name of the form which properties will be used.
 */
function xml_form_builder_create_properties_store($form_name) {
  module_load_include('inc', 'xml_form_builder', 'JSONFormDefinitionGenerator');
  $definition = new XMLFormDefinition(XMLFormDatabase::get($form_name));
  $data = JSONFormDefinitionGenerator::GetPropertiesArray($definition->getProperties());
  return [
    '#attached' => [
      'library' => ['xml_form_builder/xml-form-builder-properties-js'],
      'drupalSettings' => [
        'xml_form_builder' => ['properties' => $data],
      ],
    ],
  ];
}

/**
 * Creates an ExtJS store containing the form elements.
 *
 * Used to populate the 'Elements Tree'.
 *
 * @param string $form_name
 *   The name of the form which will be used.
 */
function xml_form_builder_create_element_store($form_name) {
  module_load_include('inc', 'xml_form_builder', 'JSONFormDefinitionGenerator');
  $definition = new XMLFormDefinition(XMLFormDatabase::get($form_name));
  $data = JSONFormDefinitionGenerator::GetFormArray($definition->getForm());
  return [
    '#attached' => [
      'library' => ['xml_form_builder/xml-form-builder-elements-js'],
      'drupalSettings' => [
        'xml_form_builder' => ['elements' => $data],
      ],
    ],
  ];
}

/**
 * Gets a list of all the existing element types defined by the elements hook.
 *
 * @return array
 *   A list of all the existing element types.
 */
function xml_form_builder_get_element_types() {
  $output = &drupal_static(__FUNCTION__);

  if (!isset($output)) {
    $types = array_keys(Drupal::getContainer()->get('plugin.manager.element_info')->getDefinitions());
    // @XXX: Supporting #type=>markup for backwards compatibility.
    // In Drupal 8 #type is not required.
    // Could be handled with NULL but Ext won't bind it to a display value.
    $types[] = 'markup';
    $output = array_combine($types, $types);
    ksort($output);
  }

  return $output;
}
