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

require_once('F3_TYPO3CR_StorageAccessTestBase.php');

/**
 * Tests for the StorageAccess_PDO implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3CR_StorageAccess_PDOTest extends F3_TYPO3CR_StorageAccessTestBase {

	/**
	 * @var string
	 */
	protected $fixtureFolder;

	/**
	 * @var string
	 */
	protected $fixtureDB;

	/**
	 * Set up the test environment
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$environment = $this->componentManager->getComponent('F3_FLOW3_Utility_Environment');
		$this->fixtureFolder = $environment->getPathToTemporaryDirectory() . 'TYPO3CR/Tests/';
		if (!is_dir($this->fixtureFolder)) {
			F3_FLOW3_Utility_Files::createDirectoryRecursively($this->fixtureFolder);
		}
		$this->fixtureDB = uniqid('sqlite') . '.db';
		copy(FLOW3_PATH_PACKAGES . 'TYPO3CR/Tests/Fixtures/TYPO3CR.db', $this->fixtureFolder . $this->fixtureDB);
		$this->storageAccess = new F3_TYPO3CR_StorageAccess_PDO('sqlite:' . $this->fixtureFolder . $this->fixtureDB);
	}

	/**
	 * Clean up after the tests
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function tearDown() {
		unlink($this->fixtureFolder . $this->fixtureDB);
	}
}
?>