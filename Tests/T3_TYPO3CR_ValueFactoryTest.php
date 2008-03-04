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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Tests for the ValueFactory implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3CR_ValueFactoryTest extends T3_Testing_BaseTestCase {

	/**
	 * @var T3_phpCR_ValueFactory
	 */
	protected $valueFactory;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$this->valueFactory = new T3_TYPO3CR_ValueFactory($this->componentManager);
	}

	/**
	 * Checks if createValue can guess the STRING type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromStringGuessesCorrectType() {
		$value = $this->valueFactory->createValue('This is a string');
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::STRING, 'New Value object was not of type STRING.');
	}

	/**
	 * Checks if createValue can guess the LONG type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromLongGuessesCorrectType() {
		$value = $this->valueFactory->createValue(10);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::LONG, 'New Value object was not of type LONG.');
	}

	/**
	 * Checks if createValue can guess the DOUBLE type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromDoubleGuessesCorrectType() {
		$value = $this->valueFactory->createValue(1.5);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::DOUBLE, 'New Value object was not of type DOUBLE.');
	}

	/**
	 * Checks if createValue can guess the BOOLEAN type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromBooleanGuessesCorrectType() {
		$value = $this->valueFactory->createValue(FALSE);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::BOOLEAN, 'New Value object was not of type BOOLEAN.');
	}

	/**
	 * Checks if createValue can guess the DATE type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromDateGuessesCorrectType() {
		$value = $this->valueFactory->createValue(new DateTime('2007-09-22'));
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::DATE, 'New Value object was not of type DATE.');
	}

	/**
	 * Checks if createValue can guess the REFERENCE type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromReferenceGuessesCorrectType() {
		$mockStorageAccess = $this->getMock('T3_TYPO3CR_StorageAccessInterface');
		$mockSession = $this->getMock('T3_TYPO3CR_Session', array(), array(), '', FALSE);
		$node = new T3_TYPO3CR_Node($mockSession, $mockStorageAccess, $this->componentManager);
		$value = $this->valueFactory->createValue($node);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::REFERENCE, 'New Value object was not of type REFERENCE.');
		$this->assertEquals($value->getString(), $node->getUUID(), 'The Value did not contain the UUID of the passed Node object.');
	}

	/**
	 * Checks if createValue can guess the BINARY type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function createValueFromBinaryGuessesCorrectType() {
		$fileHandle = fopen(FLOW3_PATH_ROOT . 'Packages/TYPO3CR/Tests/Fixtures/binaryGarbage.dat', 'rb');
		$value = $this->valueFactory->createValue($fileHandle);
		$this->assertEquals($value->getType(), T3_phpCR_PropertyType::BINARY, 'New Value object was not of type BINARY.');
		$this->assertFalse(is_resource($fileHandle), 'ValueFactory did not close a passed file handle as expected.');

			// The following differentiates between PHP with is_binary and without.
		$data = file_get_contents(FLOW3_PATH_ROOT . 'Packages/TYPO3CR/Tests/Fixtures/binaryGarbage.dat');
		$value = $this->valueFactory->createValue($data);
		if (function_exists('is_binary')) {
			$this->assertEquals($value->getType(), T3_phpCR_PropertyType::BINARY, 'New Value object was not of type BINARY.');
		} else {
			$this->assertEquals($value->getType(), T3_phpCR_PropertyType::STRING, 'New Value object was not of type STRING.');
		}
	}

	/**
	 * Checks if type conversion request for a non-string value throw an exception
	 * @test
	 */
	public function createValueThrowsExceptionIfTypeIsGivenForNonStringValue() {
		try {
			$value = $this->valueFactory->createValue(new DateTime('2007-09-22'), T3_phpCR_PropertyType::BINARY);
			$this->fail('createValue() must throw an exception if type conversion is requested for a non-string value.');
		} catch (T3_phpCR_ValueFormatException $e) {
			// fine
		}
	}

	/**
	 * Checks if type conversion works, if requested using createValue()
	 * @test
	 * @todo We cannot see the internal value variable, thus the check is somewhat flaky...
	 */
	public function createValueConvertsTypeToBooleanIfRequested() {
		$value = $this->valueFactory->createValue('Some test string', T3_phpCR_PropertyType::BOOLEAN);
		$this->assertSame($value->getType(), T3_phpCR_PropertyType::BOOLEAN, 'New Value object was not of type BOOLEAN.');
	}
}
?>
