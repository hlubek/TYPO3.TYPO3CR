<?php
declare(encoding = 'utf-8');

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
 * A Value
 *
 * @package		TYPO3CR
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3CR_Value implements T3_phpCR_ValueInterface {

	/**
	 * @var T3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var integer
	 */
	protected $type;

	/**
	 * @var $nonStreamConversionAllowed
	 */
	protected $nonStreamConversionAllowed = TRUE;

	/**
	 * @var $streamConversionAllowed
	 */
	protected $streamConversionAllowed = TRUE;

	/**
	 * Constructs a Value object from the given $value and $type arguments
	 *
	 * @param mixed $value The value of the Value object
	 * @param integer $type A type, see constants in T3_phpCR_PropertyType
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($value, $type = T3_phpCR_PropertyType::UNDEFINED) {
		$this->value = $value;
		$this->type = $type;
	}

	/**
	 * Returns a string representation of this value.
	 * 
	 * @return string A String representation of the value of this property.
	 * @throws T3_phpCR_ValueFormatException if conversion to a String is not possible. 
	 * @throws T3_phpCR_IllegalStateException if getStream has previously been called on this Value instance. 
	 * @throws T3_phpCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getString() {
		if(!$this->nonStreamConversionAllowed) {
			throw new BadMethodCallException('getStream() has previously been called on this Value object.', 1190032940);
		}
		$this->streamConversionAllowed = FALSE;

		switch($this->type) {
			case T3_phpCR_PropertyType::DATE:
				return date_format(new DateTime($this->value), DATE_ISO8601);
				break;
			default:
				return (string)$this->value;
		}
	}

	/**
	 * Returns the value as string, alias for getString()
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->getString();
	}

	/**
	 * Returns an InputStream representation of this value. Uses the standard
	 * conversion to binary (see JCR specification).
	 * It is the responsibility of the caller to close the returned InputStream.
	 * 
	 * @return InputStream An InputStream representation of this value.
	 * @throws BadMethodCallException if a non-stream get method has previously been called on this Value instance.
	 * @throws T3_phpCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implement this. We may need the resource manager first...
	 */
	public function getStream() {
		if(!$this->streamConversionAllowed) {
			throw new BadMethodCallException('A non stream get method has previously been called on this Value object.', 1190032941);
		}
		$this->nonStreamConversionAllowed = FALSE;

		throw new T3_phpCR_RepositoryException('getStream() has not yet been implemented.', 1190034714);
	}

	/**
	 * Returns a long representation of this value.
	 * 
	 * @return string A long representation of the value of this property.
	 * @throws T3_phpCR_ValueFormatException if conversion to a long is not possible. 
	 * @throws BadMethodCallException if getStream has previously been called on this Value instance. 
	 * @throws T3_phpCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getLong() {
		if(!$this->nonStreamConversionAllowed) {
			throw new BadMethodCallException('getStream() has previously been called on this Value object.', 1190032942);
		}
		$this->streamConversionAllowed = FALSE;
		return (double)$this->value;
	}

	/**
	 * Returns a double representation of this value. Is an alias for getLong().
	 * 
	 * @return string A double representation of the value of this property.
	 * @throws T3_phpCR_ValueFormatException if conversion to a double is not possible. 
	 * @throws BadMethodCallException if getStream has previously been called on this Value instance. 
	 * @throws T3_phpCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDouble() {
		return $this->getLong();
	}

	/**
	 * Returns a DateTime representation of this value.
	 * 
	 * @return DateTime A DateTime representation of the value of this property.
	 * @throws T3_phpCR_ValueFormatException if conversion to a DateTime is not possible. 
	 * @throws BadMethodCallException if getStream has previously been called on this Value instance. 
	 * @throws T3_phpCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDate() {
		if(!$this->nonStreamConversionAllowed) {
			throw new BadMethodCallException('getStream() has previously been called on this Value object.', 1190032944);
		}
		$this->streamConversionAllowed = FALSE;

		try {
			$DateTime = new DateTime($this->value);
		} catch (Exception $e) {
			throw new T3_phpCR_ValueFormatException('Conversion to a DateTime object is not possible. Cause: '.$e->getMessage(), 1190034628);
		}
		return $DateTime;
	}

	/**
	 * Returns a boolean representation of this value.
	 * 
	 * @return string A boolean representation of the value of this property.
	 * @throws T3_phpCR_ValueFormatException if conversion to a boolean is not possible. 
	 * @throws BadMethodCallException if getStream has previously been called on this Value instance. 
	 * @throws T3_phpCR_RepositoryException if another error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBoolean() {
		if(!$this->nonStreamConversionAllowed) {
			throw new BadMethodCallException('getStream() has previously been called on this Value object.', 1190032945);
		}
		$this->streamConversionAllowed = FALSE;
		return (boolean)$this->value;
	}

	/**
	 * Returns the type of this Value. One of:
	 * * T3_phpCR_PropertyType::STRING
	 * * T3_phpCR_PropertyType::DATE
	 * * T3_phpCR_PropertyType::BINARY
	 * * T3_phpCR_PropertyType::DOUBLE
	 * * T3_phpCR_PropertyType::LONG
	 * * T3_phpCR_PropertyType::BOOLEAN
	 * * T3_phpCR_PropertyType::NAME
	 * * T3_phpCR_PropertyType::PATH
	 * * T3_phpCR_PropertyType::REFERENCE
	 * * T3_phpCR_PropertyType::WEAKREFERENCE
	 * * T3_phpCR_PropertyType::URI
	 * 
	 * The type returned is that which was set at property creation.
	 * @return integer The type of the value
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getType() {
		return $this->type;
	}

}

?>