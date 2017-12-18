<?php

/**
 * @file
 * Callbacks and functions used in the Create Form Page.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\xml_form_builder\XMLFormRepository;

/**
 * Validates the name Drupal Form Element.
 *
 * Ensures that the name doesn't match any existing form names.
 *
 * @param array $element
 *   The name Drupal Form Element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Drupal Form State.
 */
function xml_form_builder_create_validate_name(array $element, FormStateInterface $form_state) {
  $form_name = $form_state->getValue('form_name');
  if (XMLFormRepository::exists($form_name)) {
    $error_msg = t('The given form name "%name" is already in use. Form names must be unique.', ['%name' => $form_name]);
    $form_state->setErrorByName('form_name', $error_msg);
  }
}

/**
 * Gets the uploaded file from the create form.
 *
 * @return DOMDocument
 *   The uploaded file if it exists, FALSE otherwise.
 */
function xml_form_builder_create_get_uploaded_file() {
  $file_uploaded = isset($_FILES['files']['error']['file']) && ($_FILES['files']['error']['file'] == 0);
  if ($file_uploaded) {
    $definition = new DOMDocument();
    $definition->load($_FILES['files']['tmp_name']['file']);
    return $definition;
  }
  return FALSE;
}
