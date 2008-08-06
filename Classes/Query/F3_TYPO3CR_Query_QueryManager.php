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
 * This class encapsulates methods for the management of search queries.
 * Provides methods for the creation and retrieval of search queries.
 *
 * @package TYPO3CR
 * @subpackage Query
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_Query_QueryManager implements F3_PHPCR_Query_QueryManagerInterface {

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * Injects the Component Factory
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @required
	 */
	public function injectComponentFactory(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Creates a new query by specifying the query statement itself and the language
	 * in which the query is stated.
	 *
	 * @param string $statement
	 * @param string $language
	 * @return F3_PHPCR_Query_QueryInterface a Query object
	 * @throws F3_PHPCR_Query_InvalidQueryException if the query statement is syntactically invalid or the specified language is not supported
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 */
	public function createQuery($statement, $language) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897622);
	}

	/**
	 * Creates a new prepared query by specifying the query statement itself and the language
	 * in which the query is stated.
	 *
	 * @param string $statement
	 * @param string $language
	 * @return F3_PHPCR_Query_PreparedQueryInterface a PreparedQuery object
	 * @throws F3_PHPCR_Query_InvalidQueryException if the query statement is syntactically invalid or the specified language is not supported
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 */
	public function createPreparedQuery($statement, $language) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897623);
	}

	/**
	 * Returns a QueryObjectModelFactory with which a JCR-JQOM query can be built
	 * programmatically.
	 *
	 * @return F3_PHPCR_Query_QOM_QueryObjectModelFactoryInterface a QueryObjectModelFactory object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getQOMFactory() {
		return $this->componentFactory->getComponent('F3_PHPCR_Query_QOM_QueryObjectModelFactoryInterface');
	}

	/*
	 * Retrieves an existing persistent query. If node is not a valid persisted
	 * query (that is, a node of type nt:query), an InvalidQueryException is thrown.
	 * Persistent queries are created by first using QueryManager.createQuery to
	 * create a Query object and then calling Query.save to persist the query to
	 * a location in the workspace.
	 *
	 * @param F3_PHPCR_NodeInterface $node a persisted query (that is, a node of type nt:query).
	 * @return F3_PHPCR_Query_QueryInterface a Query object.
	 * @throws F3_PHPCR_Query_InvalidQueryException If node is not a valid persisted query (that is, a node of type nt:query).
	 * @throws F3_PHPCR_RepositoryException if another error occurs
	 */
	public function getQuery($node) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897625);
	}

	/**
	 * Returns an array of strings representing all query languages supported by
	 * this repository. In level 1 this set must include the strings represented
	 * by the constants Query.JCR_SQL2 and Query.JCR_JQOM. An implementation of
	 * either level may also support other languages.
	 *
	 * @return array A string array.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getSupportedQueryLanguages() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1216897626);
	}

}

?>