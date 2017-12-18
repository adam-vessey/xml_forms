<?php

namespace Drupal\xml_form_api\Exception;

use Drupal\objective_forms\FormElement;
use Drupal\xml_form_api\ContextType;

/**
 * Exception for a mis-configured element context definition.
 */
class XMLFormsContextDefinitionException extends XMLFormsContextException {

  /**
   * Constructor function for the XMLFormsContextDefinitionException class.
   *
   * @param \Drupal\xml_form_api\ContextType $type
   *   The context type to build an exception for.
   * @param \Drupal\objective_forms\FormElement $element
   *   The form element being referred to when the exception is thrown.
   */
  public function __construct(ContextType $type, FormElement $element) {
    $message = "Specifies an XPath context of {$type} but none is defined. Check the form defintion";
    parent::__construct($type, $element, $message);
  }

}
