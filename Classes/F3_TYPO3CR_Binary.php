<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3CR;

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
 * @version $Id$
 */

/**
 * The Binary object allows to handle BINARY values.
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Binary implements F3::PHPCR::BinaryInterface {

	/**
	 * Returns a stream representation of this value.
	 *
	 * @return resource A stream representation of this value.
	 * @throws BadMethodCallException if acquire() has not yet or release() has already been called on this Binary object instance.
	 * @throws F3::PHPCR::RepositoryException if another error occurs.
	 */
	public function getStream() {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213021591);
	}

	/**
	 * Returns a (native) string representation of this value.
	 *
	 * @return string A (native) string representation of this value.
	 * @throws BadMethodCallException if acquire() has not yet or release() has already been called on this Binary object instance.
	 * @throws F3::PHPCR::RepositoryException if another error occurs.
	 */
	public function getBytes() {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213021592);
	}

	/**
	 * Returns the size of this value in bytes.
	 *
	 * @return integer the size of this value in bytes.
	 * @throws BadMethodCallException if acquire() has not yet or release() has already been called on this Binary object instance.
	 * @throws F3::PHPCR::RepositoryException if another error occurs.
	 */
	public function getSize() {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213021593);
	}

	/**
	 * Clients must call this method before using any of the other methods of
	 * this interface, other than release().
	 * This method is used by the implementation to track the usage of Binary
	 * objects and perform any preparation that may be necessary for returning
	 * the binary data (through either getStream() or getBytes()) or reporting
	 * the size of the binary data (through getSize()). The details of any such
	 * preparation will be specific to the implementation.
	 *
	 * @return void
	 * @throws BadMethodCallException if release() has already been called on this Binary object instance.
	 * @throws F3::PHPCR::RepositoryException if another error occurs.
	 */
	public function acquire() {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213021594);
	}

	/**
	 * Clients must call this method when they are finished with a Binary object
	 * instance in order to allow the implementation to release any resources used
	 * during the lifetime of the object. The details of any such clean-up will be
	 * specific to the implementation.
	 * It is legal for this method to be called even if acquire() has not yet been
	 * called, though in a typical implementation this will have no effect.
	 *
	 * @return void
	 * @throws F3::PHPCR::RepositoryException if an error occurs.
	 */
	public function release() {
		throw new F3::PHPCR::UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213021595);
	}

}

?>