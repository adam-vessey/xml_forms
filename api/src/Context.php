<?php

namespace Drupal\xml_form_api;

use Drupal\objective_forms\FormElement;
use Drupal\xml_form_api\Exception\XMLFormsContextDefinitionException;
use Drupal\xml_form_api\Exception\XMLFormsContextNotFoundException;

/**
 * Context info.
 *
 * Stores the type of context a Path uses, and retrieve the DOMNode a context
 * refers to.
 */
class Context {

  /**
   * The type of context this class represents.
   *
   * @var ContextType
   */
  protected $type;

  /**
   * Create XPath Context Object.
   *
   * @param ContextType $type
   *   The context for the XPath object as defined by ContextType.
   */
  public function __construct(ContextType $type) {
    $this->type = $type;
  }

  /**
   * Checks if the DOMNode for this context exists for the given FormElement.
   *
   * @param XMLDocument $document
   *   Document to check.
   * @param \Drupal\objective_forms\FormElement $element
   *   Element to check.
   *
   * @return bool
   *   TRUE/FALSE based on its existence.
   */
  public function exists(XMLDocument $document, FormElement $element) {
    try {
      $this->getNode($document->registry, $element);
      return TRUE;
    }
    catch (XMLFormsContextException $e) {
      return FALSE;
    }
  }

  /**
   * Gets the node defined by this context for the given form element.
   *
   * @param XMLDocument $document
   *   The document to get the node for.
   * @param \Drupal\objective_forms\FormElement $element
   *   The node to grab.
   *
   * @return DOMNode
   *   If the context node is found it is returned, NULL otherwise.
   */
  public function getNode(XMLDocument $document, FormElement $element) {
    switch ($this->type->val) {
      case ContextType::DOCUMENT:
        return NULL;

      case ContextType::PARENT:
        return $this->getParent($document, $element);

      case ContextType::SELF:
        return $this->getSelf($document, $element);
    }
  }

  /**
   * Gets the parent context node of the provided FormElement.
   *
   * @param XMLDocument $document
   *   The document to get the element from.
   * @param \Drupal\objective_forms\FormElement $element
   *   The element to get parent context from.
   *
   * @throws XMLFormsContextNotFoundException
   *   Does so if no parent form element context is found.
   * @throws XMLFormsContextDefinitionException
   *   Does so if no parent form element defines a create or read action.
   *
   * @return DOMNode
   *   If found the parent node is returned, otherwise NULL.
   */
  protected function getParent(XMLDocument $document, FormElement $element) {
    //dsm(func_get_args(), 'p');
    $parent = $element->parent;
    while ($parent) {
      $selected_node = isset($parent->actions->read);
      $created_node = isset($parent->actions->create);
      if ($selected_node || $created_node) {
        // We've found a parent that should have a node registered.
        if ($document->registry->isRegistered($parent->hash)) {
          return $document->registry->get($parent->hash);
        }
        dsm($parent, 'asdf');
        dsm($document, 'd');
        throw new XMLFormsContextNotFoundException($this->type, $element);
      }
      // Check next Parent.
      $parent = $parent->parent;
    }
    throw new XMLFormsContextDefinitionException($this->type, $element);
  }

  /**
   * Gets the self context node of the provided Form Element.
   *
   * @param XMLDocument $document
   *   The document to grab self context from.
   * @param \Drupal\objective_forms\FormElement $element
   *   The element to grab self context from within that document.
   *
   * @throws XMLFormsContextNotFoundException
   *   Does so if no context is found for the element.
   *
   * @return DOMNode
   *   The DOMNode for the provided Form Element's 'self' context.
   */
  protected function getSelf(XMLDocument $document, FormElement $element) {
    if ($document->registry->isRegistered($element->hash)) {
      return $document->registry->get($element->hash);
    }
    throw new XMLFormsContextNotFoundException($this->type, $element);
  }

  /**
   * Returns a string describing this context.
   *
   * @return string
   *   String describing the context.
   */
  public function __toString() {
    return (string) $this->type;
  }

}
