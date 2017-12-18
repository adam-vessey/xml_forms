<?php

namespace Drupal\xml_form_builder\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drupal\xml_form_builder\XMLFormRepository;
use Drupal\xml_form_builder\XMLFormDatabase;
use AbstractObject;

/**
 * Default controller for the xml_form_builder module.
 */
class DefaultController extends ControllerBase {

  /**
   * Show the Main page.
   *
   * Here, the user can select which action they would like to do.
   *
   * @return array
   *   The table to display.
   */
  public function xml_form_builder_main() {
    $names = XMLFormRepository::getNames();

    // No forms exist can only create.
    if (count($names) == 0) {
      return '<div>No forms are defined. Please create a new form.</div><br/>';
    }

    $table = [
      '#type' => 'table',
      '#header' => [
        ['data' => t('Title')],
        ['data' => t('Type')],
        [
          'data' => t('Operations'),
          'colspan' => 6,
        ],
      ],
      '#rows' => [],
    ];

    foreach ($names as $form_info) {
      $name = $form_info['name'];
      if ($form_info['indb']) {
        $type = t('Custom');
        $edit = Link::createFromRoute(
          t('Edit'),
          'xml_form_builder.edit',
          ['form_name' => $name]
        );
        $delete = Link::createFromRoute(
          t('Delete'),
          'xml_form_builder.delete',
          ['form_name' => $name]
        );
      }
      else {
        $type = t('Built-in');
        $edit = '';
        $delete = '';
      }
      $copy = Link::createFromRoute(
        t('Copy'),
        'xml_form_builder.copy',
        ['form_name' => $name]
      );
      $view = Link::createFromRoute(
        t('View'),
        'xml_form_builder.preview',
        ['form_name' => $name]
      );
      $export = Link::createFromRoute(
        t('Export'),
        'xml_form_builder.export',
        ['form_name' => $name]
      );
      $associate = Link::createFromRoute(
        t('Associate'),
        'xml_form_builder.associations_form',
        ['form_name' => $name]
      );

      $table['#rows'][] = [
        $name,
        $type,
        $copy,
        $edit,
        $view,
        $export,
        $delete,
        $associate,
      ];
    }
    return $table;
  }

  /**
   * Show the Associations page.
   *
   * Here, the user can view which forms are enabled for each content model.
   *
   * @return array
   *   The table to display.
   */
  public function xml_form_builder_list_associations() {
    module_load_include('inc', 'xml_form_builder', 'includes/associations');

    $associations_list = [
      '#theme' => 'item_list',
      '#items' => [],
    ];

    $associations = xml_form_builder_get_associations([], [], [], TRUE);
    $map = [];

    foreach ($associations as $association) {
      $cmodel = $association['content_model'];
      $form = $association['form_name'];
      if (!isset($map[$cmodel])) {
        $map[$cmodel] = [];
      }
      $map[$cmodel][] = $form;
    }
    ksort($map);

    // Returns a link to the edit associations form for form $form_name.
    $create_form_association_link = function ($form_name) {
      return [Link::createFromRoute($form_name, 'xml_form_builder.associations_form', ['form_name' => $form_name])];
    };

    foreach ($map as $cmodel => $forms) {
      $form_table = [
        '#type' => 'table',
        '#rows' => array_map($create_form_association_link, $forms),
      ];
      $object = islandora_object_load($cmodel);
      if ($object) {
        $label = $object->label . " ($cmodel)";
      }
      else {
        $label = $cmodel;
      }
      $associations_list['#items'][] = ['#markup' => $label . \Drupal::service("renderer")->render($form_table)];
    }

    return [$associations_list];
  }

  /**
   * Downloads the XML Form Definition to the clients computer..
   *
   * @param string $form_name
   *   The name of the form to download.
   */
  public function xml_form_builder_export($form_name) {
    header('Content-Type: text/xml', TRUE);
    header('Content-Disposition: attachment; filename="' . $form_name . '.xml"');
    $definition = XMLFormRepository::get($form_name);
    $definition->formatOutput = TRUE;
    echo $definition->saveXML();
    exit();
  }

  /**
   * Includes all the required CSS/JS files needed to render the Form Builder GUI.
   *
   * @param string $form_name
   *   The name of the form to edit.
   *
   * @return array
   *   The render array for the Form Builder.
   */
  public function xml_form_builder_edit($form_name) {
    module_load_include('inc', 'xml_form_builder', 'Edit');

    if (!XMLFormDatabase::exists($form_name)) {
      drupal_set_message(t('Form "%name" does not exist.', [
        '%name' => $form_name,
      ]), 'error');
      throw new NotFoundHttpException();
    }
    $builder = [
      '#markup' => '<div id="xml-form-builder-editor"></div>',
    ];
    $css = xml_form_builder_edit_include_css();
    $js = xml_form_builder_edit_include_js();
    $types = xml_form_builder_create_element_type_store();
    $elements = xml_form_builder_create_element_store($form_name);
    $properties = xml_form_builder_create_properties_store($form_name);
    $full_builder = array_merge_recursive($builder, $css, $js, $types, $elements, $properties);

    return $full_builder;
  }

  /**
   * Save changes made to the form definition client side.
   *
   * Transforms the submited JSON into a Form Definition which is then stored in
   * the database as an XML Form Definition.
   *
   * @param string $form_name
   *   The name of the form to update.
   *
   * @throws Exception
   *   If unable to instantiate the JSON form definition, or generate the XML form
   *   definition.
   */
  public function xml_form_builder_edit_save($form_name) {
    module_load_include('inc', 'xml_form_builder', 'JSONFormDefinition');
    try {
      $definition = new \JSONFormDefinition($_POST['data']);
      list($properties, $form) = $definition->getPropertiesAndForm();
      $definition = XMLFormDefinitionGenerator::create($properties, $form);
      XMLFormDatabase::update($form_name, $definition);
    }
    catch (Exception $e) {
      $msg = "File: {$e->getFile()}<br/>Line: {$e->getLine()}<br/>Error: {$e->getMessage()}";
      drupal_set_message(Xss::filter($msg), 'error');
    }
    return [];
  }

  /**
   * Remove the association identified by $id.
   *
   * Either by deleting it from the database, or marking it disabled if its
   * provided by a hook.
   *
   * @param string $form_name
   *   The name of the form for which the associations are being adjusted.
   *   (used to redirect).
   * @param string|int $id
   *   The identifier for the form association.  A string for "default" forms
   *   (added in via associations), and an integer for associations added via
   *   the form.
   */
  public function xml_form_builder_disable_association($form_name, $id) {
    module_load_include('inc', 'xml_form_builder', 'includes/associations');
    $association = xml_form_builder_get_association($id);
    if (!isset($association)) {
      drupal_set_message(t('Specified association does not exist.'), 'error');
      return $this->redirect('xml_form_builder.associations_form', ['form_name' => $form_name]);
    }
    // Database defined association.
    if ($association['in_db']) {
      db_delete('xml_form_builder_form_associations')
        ->condition('id', intval($id))
        ->execute();
      drupal_set_message(t('Deleted the association ID:%id from %form_name.', [
        '%id' => $id,
        '%form_name' => $form_name,
      ]));
    }
    else {
      // Hook defined association.
      $num_results = db_select('xml_form_builder_association_hooks', 'fa')
        ->fields('fa')
        ->condition('id', $id)
        ->countQuery()
        ->execute()
        ->fetchField();
      if ($num_results == 1) {
        db_update('xml_form_builder_association_hooks')
          ->fields(['enabled' => (int) FALSE])
          ->condition('id', $id)
          ->execute();
      }
      else {
        db_insert('xml_form_builder_association_hooks')
          ->fields([
            'id' => $id,
            'enabled' => (int) FALSE,
          ])
          ->execute();
      }
      drupal_set_message(t('Successfully disabled association.'));
    }
    return $this->redirect('xml_form_builder.associations_form', ['form_name' => $form_name]);
  }

  /**
   * Enable a default association identified by $id.
   *
   * @param string $form_name
   *   The name of the form for which the associations are being adjusted.
   *   (used to redirect).
   * @param string $id
   *   The identifier for the form association. Note that only "default"
   *   associations added via hook_xml_form_builder_form_associations() can be
   *   enabled.
   */
  public function xml_form_builder_enable_association($form_name, $id) {
    module_load_include('inc', 'xml_form_builder', 'includes/associations');
    $association = xml_form_builder_get_association($id);
    if (!isset($association)) {
      drupal_set_message(t('Specified association does not exist.'), 'error');
      return $this->redirect('xml_form_builder.associations_form', ['form_name' => $form_name]);
    }
    // Hook defined association, can't enable non hook associations.
    if (!$association['in_db']) {
      $num_results = db_select('xml_form_builder_association_hooks', 'fa')
        ->fields('fa')
        ->condition('id', $id)
        ->countQuery()
        ->execute()
        ->fetchField();
      if ($num_results == 1) {
        db_update('xml_form_builder_association_hooks')
          ->fields(['enabled' => (int) TRUE])
          ->condition('id', $id)
          ->execute();
      }
      else {
        db_insert('xml_form_builder_association_hooks')
          ->fields([
            'id' => $id,
            'enabled' => (int) TRUE,
          ])
          ->execute();
      }
    }
    drupal_set_message(t('Successfully enabled association.'));
    return $this->redirect('xml_form_builder.associations_form', ['form_name' => $form_name]);
  }

  /**
   * A form for adding datastreams to an object.
   */
  public function xml_form_builder_add_datastream_page(AbstractObject $object) {
    module_load_include('inc', 'xml_form_builder', 'includes/datastream.form');
    return [
      'core_form' => $this->formBuilder()->getForm('\Drupal\islandora\Form\IslandoraAddDatastreamForm', $object),
      'xml_form_fieldset' => (xml_form_builder_empty_metadata_datastreams($object) ?
        [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => t('Add A Metadata Datastream'),
          'xml_form' => $this->formBuilder()->getForm('\Drupal\xml_form_builder\Form\XmlFormBuilderCreateMetadataForm', $object),
        ] :
        []
      ),
    ];
  }

}
