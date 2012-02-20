<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Functional test case which covers all workspace-related behavior of the
 * content repository.
 *
 */
class WorkspacesTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3\Domain\Service\ContentContext
	 */
	protected $personalContext;

	/**
	 * @var \TYPO3\TYPO3\Domain\Model\Node
	 */
	protected $rootNode;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->personalContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('user-robert');
		$this->rootNode = $this->personalContext->getWorkspace()->getRootNode();
	}

	/**
	 * @test
	 */
	public function nodesCreatedInAPersonalWorkspacesCanBeRetrievedAgainInThePersonalContext() {
		$fooNode = $this->rootNode->createNode('foo');
		$this->assertSame($fooNode, $this->rootNode->getNode('foo'));

		$this->persistenceManager->persistAll();

		$this->assertSame($fooNode, $this->rootNode->getNode('foo'));
	}

	/**
	 * @test
	 */
	public function nodesCreatedInAPersonalWorkspacesAreNotVisibleInTheLiveWorkspace() {
		$this->rootNode->createNode('homepage')->createNode('about');

		$this->persistenceManager->persistAll();

		$liveContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		$liveRootNode = $liveContext->getWorkspace()->getRootNode();

		$this->assertNull($liveRootNode->getNode('/homepage/about'));
	}

	/**
	 * @test
	 */
	public function nodesCreatedInAPersonalWorkspacesAreNotVisibleInTheLiveWorkspaceEvenWithoutPersistAll() {
		$this->rootNode->createNode('homepage')->createNode('imprint');

		$liveContext = new \TYPO3\TYPO3\Domain\Service\ContentContext('live');
		$liveRootNode = $liveContext->getWorkspace()->getRootNode();

		$this->assertNull($liveRootNode->getNode('/homepage/imprint'));
	}
}

?>
