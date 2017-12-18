<?php

namespace Drupal\xml_form_builder\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\xml_form_api\XMLForm;

/**
 * Render the preview form.
 *
 * This shows the user what their form will look like. When it is submitted it
 * will show the user what the generated XML looks like.
 */
class XmlFormBuilderPreview extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xml_form_builder_preview';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, $form_name = NULL) {
    dsm(func_get_args(), 'args');
    $form = xml_form_builder_get_form($form, $form_state, $form_name);
    //dsm($form, 'form');
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $xml_form = new XMLForm($form_state);
      $document = $xml_form->submit($form, $form_state);
      module_load_include('inc', 'php_lib', 'DOMHelpers');
      dom_document_pretty_print($document->document);
      exit();
    }
    catch (Exception $e) {
      $message = 'File: ' . $e->getFile() . '</br>';
      $message .= 'Line: ' . $e->getLine() . '</br>';
      $message .= 'Error: ' . $e->getMessage() . '</br>';
      drupal_set_message(Xss::filter($message), 'error');
      $form_state->setRebuild(TRUE);
    }
  }

}
