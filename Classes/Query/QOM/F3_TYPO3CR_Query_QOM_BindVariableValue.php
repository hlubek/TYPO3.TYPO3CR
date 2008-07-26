<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 */

/**
 * Evaluates to the value of a bind variable.
 *
 * The query is invalid if no value is bound to bindVariableName.
 *
 * The query is invalid if bindVariableName is not a valid JCR prefix.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_TYPO3CR_Query_QOM_BindVariableValue extends F3_TYPO3CR_Query_QOM_StaticOperand implements F3_PHPCR_Query_QOM_BindVariableValueInterface {

	/**
	 * @var string
	 */
	protected $variableName;

	/**
	 * Constructs this BindVariableValue instance
	 *
	 * @param string $variableName
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($variableName) {
		$this->variableName = $variableName;
	}

	/**
	 * Gets the name of the bind variable.
	 *
	 * @return string the bind variable name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBindVariableName() {
		return $this->variableName;
	}

}

?>