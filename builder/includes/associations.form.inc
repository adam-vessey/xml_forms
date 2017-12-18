<?php

/**
 * @file
 * Helpers for the associations form.
 *
 * This is then used to associate XML forms with a given content model's
 * datastream.
 */

use Drupal\xml_form_builder\XMLFormRepository;

/**
 * Get an array of "array paths" from which an object label may be extracted.
 *
 * @param string $form_name
 *   The name of the form in question.
 *
 * @return array
 *   An array of options from which a title may be extracted.
 */
function xml_form_builder_get_title_options($form_name) {
  if (isset($form_name)) {
    $doc = XMLFormRepository::get($form_name);
    if ($doc) {
      $xpath = new DOMXPath($doc);
      $result = $xpath->query('//element[@name]');
      $elements = dom_node_list_to_array($result);
      if (count($elements) > 0) {
        $output = [NULL => t('Do not set the label')];
        foreach ($elements as $element) {
          $result = $xpath->query('ancestor-or-self::node()[@name]', $element);
          $ancestors = dom_node_list_to_array($result);
          $ancestors_array = [];
          if (count($ancestors) > 0) {
            foreach ($ancestors as $ancestor) {
              $name = $ancestor->getAttribute('name');
              $ancestors_array[] = (string) $name;
            }
          }
          $output[serialize($ancestors_array)] = "['" . implode("']['", $ancestors_array) . "']";
        }
        return $output;
      }
    }
  }
  return [NULL => t('Do not set the label')];
}
