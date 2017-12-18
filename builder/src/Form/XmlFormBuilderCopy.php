<?php

namespace Drupal\xml_form_builder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drupal\xml_form_builder\XMLFormRepository;

/**
 * Form for copying XML forms.
 */
class XmlFormBuilderCopy extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xml_form_builder_copy';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, $form_name = NULL) {
    $form_state->loadInclude('xml_form_builder', 'inc', 'Copy');
    $form_state->loadInclude('xml_form_api', 'inc', 'XMLFormDefinition');
    if (isset($_POST['cancel'])) {
      return $this->redirect('xml_form_builder.main');
    }
    if (!XMLFormRepository::exists($form_name)) {
      drupal_set_message($this->t('Form "%name" does not exist.', [
        '%name' => $form_name,
      ]), 'error');
      throw new NotFoundHttpException();
    }
    return [
      'original' => [
        '#type' => 'hidden',
        '#value' => $form_name,
      ],
      'form_name' => [
        '#type' => 'textfield',
        '#title' => $this->t('Form Name'),
        '#required' => TRUE,
        '#element_validate' => [
          'xml_form_builder_copy_validate_name',
        ],
      ],
      'copy' => [
        '#type' => 'submit',
        '#value' => $this->t('Copy'),
        '#name' => 'copy',
      ],
      'cancel' => [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#name' => 'cancel',
      ],
    ];
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('xml_form_api', 'inc', 'XMLFormDefinition');
    if ($form_state->getTriggeringElement()['#name'] == 'copy') {
      $original = $form_state->getValue(['original']);
      $form_name = $form_state->getValue(['form_name']);
      if (XMLFormRepository::copy($original, $form_name)) {
        drupal_set_message($this->t('Successfully copied form "%name".', [
          '%name' => $form_name,
        ]));
        $form_state->setRedirect('xml_form_builder.edit', ['form_name' => $form_name]);
        return;
      }
      drupal_set_message($this->t('Failed to copy form "%name".', [
        '%name' => $form_name,
      ]), 'error');
    }
    $form_state->setRedirect('xml_form_builder.main');
  }

}
