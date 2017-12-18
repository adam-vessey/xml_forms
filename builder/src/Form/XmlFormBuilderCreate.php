<?php

namespace Drupal\xml_form_builder\Form;

use DOMDocument;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\xml_form_builder\XMLFormRepository;

/**
 * Form for creating an XML form.
 */
class XmlFormBuilderCreate extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xml_form_builder_create';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $form_state->loadInclude('xml_form_builder', 'inc', 'Create');
    if (isset($_POST['cancel'])) {
      return $this->redirect('xml_form_builder.main');
    }
    $return = [];

    $return['#attributes'] = ['enctype' => "multipart/form-data"];

    $return['form_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form Name'),
      '#required' => TRUE,
      '#maxlength' => 128,
      '#element_validate' => [
        'xml_form_builder_create_validate_name',
      ],
    ];

    if ($type == 'import') {
      $return['file'] = [
        '#type' => 'file',
        '#title' => $this->t('Form Definition'),
        '#size' => 64,
        '#description' => $this->t('An optional XML form definition template.'),
      ];
    }

    $return['create'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
      '#name' => 'create',
    ];

    $return['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#name' => 'cancel',
    ];

    return $return;
  }

  /**
   * Validate the create form.
   *
   * Makes sure the uploaded file is valid.
   *
   * @param array $form
   *   The Drupal Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal Form State.
   *
   * @throws Exception
   *   If unable to load the uploaded file as XML, or if the form definition is
   *   invalid.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('xml_form_api', 'inc', 'XMLFormDefinition');
    $file_uploaded = isset($_FILES['files']['error']['file']) && ($_FILES['files']['error']['file'] == 0);
    if ($file_uploaded) {
      $filename = $_FILES['files']['tmp_name']['file'];
      $definition = new DOMDocument();
      try {
        $definition->load($filename);
      }
      catch (Exception $e) {
        $form_state->setErrorByName('files', $this->t("Could not load uploaded file as XML, with error: %error.", [
          '%error' => $e->getMessage(),
        ]));
      }
      try {
        $version = \XMLFormDefinition::getVersion($definition);
        if (!\XMLFormDefinition::isValid($definition, $version)) {
          $form_state->setErrorByName('files', $this->t('The given form definition is not valid.'));
        }
      }

      catch (Exception $e) {
        $form_state->setErrorByName('files', $e->getMessage());
      }
    }
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('xml_form_api', 'inc', 'XMLFormDefinition');
    $form_name = $form_state->getValue(['form_name']);
    if ($form_state->getTriggeringElement()['#name'] == 'create') {
      $definition = xml_form_builder_create_get_uploaded_file();
      $definition = $definition ? $definition : xml_form_api_get_empty_form_definition();
      if (XMLFormRepository::create($form_name, $definition)) {
        drupal_set_message($this->t('Successfully created form "%name".', [
          '%name' => $form_name,
        ]));
        $form_state->setRedirect('xml_form_builder.edit', ['form_name' => $form_name]);
        return;
      }
      else {
        drupal_set_message($this->t('Failed to create form %name.', [
          '%name' => $form_name,
        ]), 'error');
      }
    }
    $form_state->setRedirect('xml_form_builder.main');
  }

}
