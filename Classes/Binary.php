<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR;

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
 * A Binary object holds a JCR property value of type BINARY.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Binary implements \F3\PHPCR\BinaryInterface {

	/**
	 * Returns a stream representation of this value.
	 * Each call to getStream() returns a new stream.
	 * The API consumer is responsible for calling close() on the returned
	 * stream.
	 *
	 * @return resource A stream representation of this value.
	 * @throws \BadMethodCallException if dispose() has already been called on this Binary
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @api
	 */
	public function getStream() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213021591);
	}

	/**
	 * Reads successive bytes from the specified position in this Binary into
	 * the passed string until $limit or the end of the Binary is encountered
	 * (whichever comes first).
	 *
	 * @param string $bytes the buffer into which the data is read.
	 * @param integer $position the position in this Binary from which to start reading bytes.
	 * @param integer $limit how many bytes to read, unlimited by default
	 * @return integer the number of bytes read into the buffer
	 * @throws \RuntimeException if an I/O error occurs.
	 * @throws \InvalidArgumentException if offset is negative.
	 * @throws \BadMethodCallException if dispose() has already been called on this Binary
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 * @api
	 */
	public function read(&$bytes, $position, $limit = 0) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1224505396);
	}

	/**
	 * Returns the size of this Binary value in bytes.
	 *
	 * @return integer the size of this value in bytes.
	 * @throws \BadMethodCallException if dispose() has already been called on this Binary
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @api
	 */
	public function getSize() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213021593);
	}

	/**
	 * Releases all resources associated with this Binary object and informs the
	 * repository that these resources may now be reclaimed.
	 * An application should call this method when it is finished with the
	 * Binary object.
	 *
	 * @return void
	 * @api
	 */
	public function dispose() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245700808);
	}

}

?>