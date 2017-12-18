<?php

namespace Drupal\xml_form_api;

use Drupal\objective_forms\FormElement;

/**
 * Encapsulates the required data to perform a CRUD action.
 */
class XMLFormProcessAction {

  /**
   * ActionInterface to perform.
   *
   * @var ActionInterface
   */
  public $action;

  /**
   * FormElement to be processed.
   *
   * @var \Drupal\objective_forms\FormElement
   */
  public $element;

  /**
   * Submitted Form Value.
   *
   * @var mixed
   */
  public $value;

  /**
   * Creates an XMLFormProcessAction instance.
   *
   * @param ActionInterface $action
   *   The appropriate action that will be performed.
   * @param \Drupal\objective_forms\FormElement $element
   *   The FormElement that the action will be performed on.
   * @param mixed $value
   *   The value that will be used in execution.
   */
  public function __construct(ActionInterface $action, FormElement $element, $value = NULL) {
    $this->action = $action;
    $this->element = $element;
    $this->value = $value;
  }

  /**
   * Executes the action.
   *
   * @param XMLDocument $document
   *   The document to execute on.
   *
   * @return bool
   *   Generally TRUE on execution and FALSE otherwise. Specifics depend on the
   *   implemented ActionInterface in XMLFormProcessAction::__construct().
   */
  public function execute(XMLDocument $document) {
    return $this->action->execute($document, $this->element, $this->value);
  }

}
