<?php

namespace Drupal\xml_form_api\Exception;

use Drupal\objective_forms\FormElement;
use Drupal\xml_form_api\ContextType;

use Exception;

/**
 * Represents exceptions that can occur when looking for the context DOMNode.
 */
class XMLFormsContextException extends Exception {

  /**
   * Constructor function for the XMLFormsContextException class.
   *
   * @param ContextType $type
   *   The context type to build an exception for.
   * @param \Drupal\objective_forms\FormElement $element
   *   The form element being referred to when the exception is thrown.
   * @param string $message
   *   The error message to throw.
   */
  public function __construct(ContextType $type, FormElement $element, $message) {
    $variable_description = "The Form Element<br/>";
    $variable_description .= "&nbsp;Location: '{$element->getLocation()}'<br/>";
    $variable_description .= "&nbsp;Title: '{$element['#title']}'</br>";
    $variable_description .= "&nbsp;Type: '{$element['#type']}'</br>";
    $variable_description .= "&nbsp;Context: '$type->val'</br>";
    $message = $variable_description . 'Error: ' . $message;
    parent::__construct($message, 0);
  }

  /**
   * String to describe the error.
   *
   * @return string
   *   String to return.
   */
  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }

}
