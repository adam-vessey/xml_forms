<?php

namespace Drupal\xml_form_api\Exception;

use Drupal\objective_forms\FormElement;
use Drupal\xml_form_api\ContextType;

/**
 * The given context DOMNode could not be found.
 *
 * In some cases, this is acceptable; in others, it is not.
 */
class XMLFormsContextNotFoundException extends XMLFormsContextException {

  /**
   * Constructor function for the XMLFormsContextNotFoundException class.
   *
   * @param \Drupal\xml_form_api\ContextType $type
   *   The context type to build an exception for.
   * @param \Drupal\objective_forms\FormElement $element
   *   The form element being referred to when the exception is thrown.
   */
  public function __construct(ContextType $type, FormElement $element) {
    $message = "The DOMNode associated with the context {$type->val} was not found.";
    parent::__construct($type, $element, $message);
  }

}
