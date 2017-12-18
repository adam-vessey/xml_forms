<?php

namespace Drupal\xml_form_api;

use Drupal\php_lib\Enum;

/**
 * Enumerated types for Context.
 */
class ContextType extends Enum {
  const __DEFAULT = 'document';
  const DOCUMENT = 'document';
  const PARENT = 'parent';
  const SELF = 'self';

  // @deprecated Constants
  // @codingStandardsIgnoreStart
  const __default = self::__DEFAULT;
  const Document = self::DOCUMENT;
  const Parent = self::PARENT;
  const Self = self::SELF;
  // @codingStandardsIgnoreEnd

}
