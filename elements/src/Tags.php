<?php
namespace Drupal\xml_form_elements;

class Tags {

  /**
   * Constants
   */
  const ADD_BUTTON = 'add-tag';

  // @deprecated Constants
  // @codingStandardsIgnoreStart
  const AddButton = self::ADD_BUTTON;
  // @codingStandardsIgnoreEnd

  /**
   * Loads the required resources for displaying the Tabs element.
   *
   * @param array $form_state
   *   The Drupal form state.
   *
   * @staticvar bool $load
   *   Keeps us from loading the same files multiple times; while not required,
   *   it just saves some time.
   */
  public static function addRequiredResources(&$form_state) {
    static $load = TRUE;
    if ($load) {
      $form_state->loadInclude('xml_form_elements', 'inc', 'includes/Element');
      $form_state->loadInclude('xml_form_elements', 'inc', 'includes/Tag');
      Element::addJS('tags.js');
      Element::addCSS('tags.css');
      $load = FALSE;
    }
  }

  /**
   * Processes the element.
   *
   * @param array $element
   *   The element to process.
   * @param array $form_state
   *   The Drupal form state.
   * @param array $complete_form
   *   The completed form.
   *
   * @return array
   *   The processed element.
   */
  public static function Process(array $element, array &$form_state, array $complete_form = NULL) {
    self::addRequiredResources($form_state);
    $element['#id'] = $element['#hash'];
    $element['#size'] = isset($element['#size']) ? $element['#size'] : 60;
    $element['#tree'] = TRUE;
    $element['#after_build'] = array('xml_form_elements_after_build_tags');
    $element['#prefix'] = "<div class='clear-block' id='ahah-{$element['#id']}-wrapper'>";
    $element['#suffix'] = '</div>';
    foreach (\Drupal\Core\Render\Element::children($element) as $key) {
      $child = $element[$key];
      $hash = $child['#hash'];
      $element['controls']['edit'][$hash] = self::createEditButton($element, $child);
      $element['controls']['remove'][$hash] = self::createRemoveButton($element, $child);
    }
    $element['controls']['add'] = self::createAddButton($element);
    return $element;
  }

  /**
   * Functionality to be passed to xml_form_elements_after_build_tags() below.
   *
   * @param array $form_element
   *   The tag element.
   * @param array $form_state
   *   The Drupal form state.
   *
   * @return array
   *   The modified $form_element.
   */
  public static function AfterBuild(array $form_element, array &$form_state) {
    if ($form_element['#required']) {
      // 2 to include the controls element.
      $require = (count(\Drupal\Core\Render\Element::children($form_element)) == 2) ? TRUE : FALSE;
      foreach (\Drupal\Core\Render\Element::children($form_element) as $key) {
        $child = &$form_element[$key];
        $child['#required'] = $require;
      }
    }
    return $form_element;
  }

  /**
   * Creates a button that allows tags to duplicate.
   *
   * @param array $element
   *   The tag form element.
   *
   * @return FormElement
   *   The "add" button.
   */
  protected static function createAddButton(array &$element) {
    $button['#type'] = 'button';
    $button['#weight'] = 4;
    $button['#size'] = 30;
    $button['#id'] = $button['#name'] = $element['#hash'] . '-add';
    $button['#attributes'] = array('style' => 'display:none');
    $button['#value'] = t('Add');
    $button['#limit_validation_errors'][] = $element['#parents'];
    $button['#ajax'] = array(
      'callback' => 'xml_form_elements_ajax_callback',
      'params' => array(
        'render' => $element['#hash'],
        'target' => $element['#hash'],
        'action' => 'add',
      ),
      'wrapper' => "ahah-{$element['#id']}-wrapper",
      'method' => 'replaceWith',
      'effect' => 'fade',
    );
    return $button;
  }

  /**
   * Creates a remove button that allows the user to remove this tag.
   *
   * @param array $element
   *   The tag form element.
   * @param array $child
   *   The #hash value of the child element being removed; used to create the
   *   button #id.
   *
   * @return FormElement
   *   The "remove" button.
   */
  protected static function createRemoveButton(array &$element, array &$child) {
    $button['#type'] = 'button';
    $button['#weight'] = 4;
    $button['#size'] = 30;
    $button['#id'] = $button['#name'] = $child['#hash'] . '-remove';
    $button['#attributes'] = array('style' => 'display:none');
    $button['#limit_validation_errors'][] = $element['#parents'];
    $button['#ajax'] = array(
      'callback' => 'xml_form_elements_ajax_callback',
      'params' => array(
        'render' => $element['#hash'],
        'target' => $element['#hash'],
        'child' => $child['#hash'],
        'action' => 'delete',
      ),
      'wrapper' => "ahah-{$element['#id']}-wrapper",
      'method' => 'replaceWith',
      'effect' => 'fade',
    );
    return $button;
  }

  /**
   * Creates a remove button that allows the user to edit this tag.
   *
   * @param array $element
   *   The tag form element.
   * @param array $child
   *   The #hash value of the child element being edited; used to create the
   *   button #id.
   *
   * @return FormElement
   *   The "edit" button.
   */
  protected static function createEditButton(array &$element, array &$child) {
    $button['#type'] = 'button';
    $button['#weight'] = 4;
    $button['#size'] = 30;
    $button['#id'] = $button['#name'] = $child['#hash'] . '-edit';
    $button['#attributes'] = array('style' => 'display:none');
    $button['#limit_validation_errors'][] = $element['#parents'];
    $button['#ajax'] = array(
      'callback' => 'xml_form_elements_ajax_callback',
      'params' => array(
        'render' => $element['#hash'],
        'target' => $element['#hash'],
        'child' => $child['#hash'],
        'action' => 'edit',
      ),
      'wrapper' => "ahah-{$element['#id']}-wrapper",
      'method' => 'replaceWith',
      'effect' => 'fade',
    );
    return $button;
  }

}
