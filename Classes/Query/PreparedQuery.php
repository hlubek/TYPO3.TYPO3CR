<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Query;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 */

/**
 * A prepared query. A new prepared query is created by calling
 * QueryManager->createPreparedQuery.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class PreparedQuery extends \F3\TYPO3CR\Query\Query implements \F3\PHPCR\Query\PreparedQueryInterface {

	/**
	 * @var array
	 */
	protected $boundVariables = array();

	/**
	 * Binds the given value to the variable named $varName.
	 *
	 * @param string $varName name of variable in query
	 * @param \F3\PHPCR\ValueInterface $value value to bind
	 * @return void
	 * @throws \InvalidArgumentException if $varName is not a valid variable in this query.
	 * @throws RepositoryException if an error occurs.
	 */
	public function bindValue($varName, \F3\PHPCR\ValueInterface $value) {
		if (array_key_exists($varName, $this->boundVariables) === FALSE) {
			throw new \InvalidArgumentException('Invalid variable name "' . $varName . '" given to bindValue.', 1217241834);
		}
		$this->boundVariables[$varName] = $value->getString();
	}

	/**
	 * Returns the values of all bound variables.
	 *
	 * @return array()
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBoundVariableValues() {
		return $this->boundVariables;
	}
}

?>