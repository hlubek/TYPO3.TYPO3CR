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
 * Tests for the Property implementation of TYPO3CR
 *
 * @package		TYPO3CR
 * @subpackage	Tests
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TYPO3CR_PropertyTest extends T3_Testing_BaseTestCase {

	/**
	 * Checks if getValue returns a Value object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getValueReturnsAValueObject() {
		$mockStorageAccess = $this->getMock('T3_TYPO3CR_StorageAccessInterface');
		$mockSession = $this->getMock('T3_TYPO3CR_Session', array(), array(), '', FALSE);
		$mockNode = $this->getMock('T3_TYPO3CR_Node', array(), array(), '', FALSE);

		$property = new T3_TYPO3CR_Property('testproperty', 'testvalue', $mockNode, FALSE, $mockSession, $mockStorageAccess, $this->componentManager);
		$valueObject = $property->getValue();
		$this->assertType('T3_phpCR_ValueInterface', $valueObject, 'getValue() a Value object.');
	}

	/**
	 * Checks if getValues returns an exception if called with on a single value
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getValuesReturnsAnExceptionIfCalledOnSingleValue() {
		$mockStorageAccess = $this->getMock('T3_TYPO3CR_StorageAccessInterface');
		$mockSession = $this->getMock('T3_TYPO3CR_Session', array(), array(), '', FALSE);
		$mockNode = $this->getMock('T3_TYPO3CR_Node', array(), array(), '', FALSE);

		$property = new T3_TYPO3CR_Property('testproperty', 'testvalue', $mockNode, FALSE, $mockSession, $mockStorageAccess, $this->componentManager);

		try {
			$valueObject = $property->getValues();
			$this->fail('getValues needs to return an exception if called on a single value');
		} catch (T3_phpCR_ValueFormatException $e) {
		}
	}

	/**
	 * Checks if getPath works as expected
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPathReturnsPathToProperty() {
		$mockStorageAccess = $this->getMock('T3_TYPO3CR_StorageAccessInterface');
		$mockRepository = $this->getMock('T3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockSession = $this->getMock('T3_TYPO3CR_Session', array(), array('workspaceName', $mockRepository, $mockStorageAccess, $this->componentManager));

		$rootNode = new T3_TYPO3CR_Node($mockSession, $mockStorageAccess, $this->componentManager);
		$rootNode->initializeFromArray(array(
			'id' => NULL,
			'pid' => 0,
			'name' => '',
			'uuid' => $rootNode->getUUID(),
			'nodetype' => 1)
		);
		$mockSession->expects($this->once())->method('getNodeByUUID')->will($this->returnValue($rootNode));
		$node = new T3_TYPO3CR_Node($mockSession, $mockStorageAccess, $this->componentManager);
		$node->initializeFromArray(array(
			'pid' => $rootNode->getUUID(),
			'name' => 'testnode',
			'uuid' => $node->getUUID(),
			'nodetype' => 1,
		));
		$node->setProperty('testproperty', 'some test value');

		$testProperty = $node->getProperty('testproperty');
		$propertyPath = $testProperty->getPath();
		$this->assertEquals($propertyPath, '/testnode/testproperty', 'The path '.$propertyPath.' was not correct.');
	}
}
?>
