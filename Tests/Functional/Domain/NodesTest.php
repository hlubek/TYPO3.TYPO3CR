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

use \TYPO3\TYPO3\Domain\Service\ContentContext;

/**
 * Functional test case which covers all Node-related behavior of the
 * content repository as long as they reside in the live workspace.
 *
 */
class NodesTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @test
	 */
	public function nodesCreatedInTheLiveWorkspacesCanBeRetrievedAgainInTheLiveContext() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$fooNode = $rootNode->createNode('foo');
		$this->assertSame($fooNode, $rootNode->getNode('foo'));

		$this->persistenceManager->persistAll();

		$this->assertSame($fooNode, $rootNode->getNode('foo'));
	}

	/**
	 * @test
	 */
	public function removedNodesCannotBeRetrievedAnymore() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$rootNode->createNode('quux');
		$rootNode->getNode('quux')->remove();
		$this->assertNull($rootNode->getNode('quux'));

		$barNode = $rootNode->createNode('bar');
		$barNode->remove();
		$this->persistenceManager->persistAll();
		$this->assertNull($rootNode->getNode('bar'));

		$rootNode->createNode('baz');
		$this->persistenceManager->persistAll();
		$rootNode->getNode('baz')->remove();
		$bazNode = $rootNode->getNode('baz');
			// workaround for PHPUnit trying to "render" the result *if* not NULL
		$bazNodeResult = $bazNode === NULL ? NULL : 'instance-of-' . get_class($bazNode);
		$this->assertNull($bazNodeResult);
	}

	/**
	 * @test
	 */
	public function removedNodesAreNotCountedAsChildNodes() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$rootNode->createNode('foo');
		$rootNode->getNode('foo')->remove();

		$this->assertFalse($rootNode->hasChildNodes(), 'First check.');

		$rootNode->createNode('bar');
		$this->persistenceManager->persistAll();

		$this->assertTrue($rootNode->hasChildNodes(), 'Second check.');

		$context = new ContentContext('user-admin');
		$rootNode = $context->getWorkspace()->getRootNode();

		$rootNode->getNode('bar')->remove();
		$this->persistenceManager->persistAll();

		$this->assertFalse($rootNode->hasChildNodes(), 'Third check.');
	}

	/**
	 * @test
	 */
	public function creatingAChildNodeAndRetrievingItAfterPersistAllWorks() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$firstLevelNode = $rootNode->createNode('firstlevel');
		$secondLevelNode = $firstLevelNode->createNode('secondlevel');
		$thirdLevelNode = $secondLevelNode->createNode('thirdlevel');

		$this->persistenceManager->persistAll();

		$retrievedNode = $rootNode->getNode('/firstlevel/secondlevel/thirdlevel');
		$this->assertSame($thirdLevelNode, $retrievedNode);
	}

	/**
	 * @test
	 */
	public function threeCreatedNodesCanBeRetrievedInSameOrder() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parent');
		$node1 = $parentNode->createNode('node1');
		$node2 = $parentNode->createNode('node2');
		$node3 = $parentNode->createNode('node3');

		$this->assertTrue($parentNode->hasChildNodes());
		$childNodes = $parentNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3), $childNodes);

		$this->persistenceManager->persistAll();

		$this->assertTrue($parentNode->hasChildNodes());
		$childNodes = $parentNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3), $childNodes);
	}

	/**
	 * @test
	 */
	public function threeChildNodesOfTheRootNodeCanBeRetrievedInSameOrder() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$node1 = $rootNode->createNode('node1');
		$node2 = $rootNode->createNode('node2');
		$node3 = $rootNode->createNode('node3');

		$this->assertTrue($rootNode->hasChildNodes(), 'child node check before persistAll()');
		$childNodes = $rootNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3), $childNodes);

		$this->persistenceManager->persistAll();

		$this->assertTrue($rootNode->hasChildNodes(), 'child node check after persistAll()');
		$childNodes = $rootNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3), $childNodes);
	}

	/**
	 * @test
	 */
	public function moveBeforeMovesNodesBeforeOthersWithoutPersistAll() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeG = $parentNode->createNode('childNodeG');

		$childNodeC->moveBefore($childNodeD);

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();
		$this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
	}

	/**
	 * @test
	 */
	public function moveBeforeNodesWithLowerIndexMovesNodesBeforeOthersWithPersistAll() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeG = $parentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$childNodeC->moveBefore($childNodeD);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveBeforeNodesWithHigherIndexMovesNodesBeforeOthersWithPersistAll() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeG = $parentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$childNodeF->moveBefore($childNodeG);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveBeforeNodesWithHigherIndexMovesNodesBeforeOthersWithoutPersistAll() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeG = $parentNode->createNode('childNodeG');

		$childNodeF->moveBefore($childNodeG);

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveAfterNodesWithLowerIndexMovesNodesAfterOthersWithoutPersistAll() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeG = $parentNode->createNode('childNodeG');

		$childNodeC->moveAfter($childNodeB);

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveAfterNodesWithLowerIndexMovesNodesAfterOthersWithPersistAll() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeG = $parentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$childNodeC->moveAfter($childNodeB);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveAfterNodesWithHigherIndexMovesNodesAfterOthersWithPersistAll() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeG = $parentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$childNodeF->moveAfter($childNodeE);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveAfterNodesWithHigherIndexMovesNodesAfterOthersWithoutPersistAll() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeG = $parentNode->createNode('childNodeG');

		$childNodeF->moveAfter($childNodeE);

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveBeforeInASeparateWorkspaceLeadsToCorrectSortingAcrossWorkspaces() {
		$liveContext = new ContentContext('live');
		$rootNode = $liveContext->getWorkspace()->getRootNode();

		$liveParentNode = $rootNode->createNode('parentNode');
		$childNodeA = $liveParentNode->createNode('childNodeA');
		$childNodeC = $liveParentNode->createNode('childNodeC');
		$childNodeD = $liveParentNode->createNode('childNodeD');
		$childNodeE = $liveParentNode->createNode('childNodeE');
		$childNodeG = $liveParentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$userContext = new ContentContext('live2');
		$userParentNode = $userContext->getNode('/parentNode');

		$childNodeB = $userParentNode->createNode('childNodeB');
		$childNodeB->moveBefore($childNodeC);

		$childNodeF = $userParentNode->createNode('childNodeF');
		$childNodeF->moveBefore($childNodeG);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $userParentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * Testcase for bug #34291 (TYPO3CR reordering does not take unpersisted
	 * node order changes into account)
	 *
	 * The error can be reproduced in the following way:
	 *
	 * - First, create some nodes, and persist.
	 * - Then, move a node after another one, filling the LAST free sorting index between the nodes. Do NOT persist after that.
	 * - After that, try to *again* move a node to this spot. In this case, we need to *renumber*
	 *   the node indices, and the system needs to take the before-moved node into account as well.
	 *
	 * The bug tested by this testcase led to wrong orderings on the flow3org website in
	 * the documentation part under some circumstances.
	 *
	 * @test
	 */
	public function renumberingTakesUnpersistedNodeOrderChangesIntoAccount() {
		$liveContext = new ContentContext('live');
		$rootNode = $liveContext->getWorkspace()->getRootNode();

		$liveParentNode = $rootNode->createNode('parentNode');
		$nodes = array();
		$nodes[1] = $liveParentNode->createNode('node001');
		$nodes[1]->setIndex(1);
		$nodes[2] = $liveParentNode->createNode('node002');
		$nodes[2]->setIndex(2);
		$nodes[3] = $liveParentNode->createNode('node003');
		$nodes[3]->setIndex(4);
		$nodes[4] = $liveParentNode->createNode('node004');
		$nodes[4]->setIndex(5);

		$this->persistenceManager->persistAll();

		$nodes[1]->moveAfter($nodes[2]);
		$nodes[3]->moveAfter($nodes[2]);

		$this->persistenceManager->persistAll();

		$actualChildNodes = $liveParentNode->getChildNodes();

		$newNodeOrder = array(
			$nodes[2],
			$nodes[3],
			$nodes[1],
			$nodes[4]
		);
		$this->assertSameOrder($newNodeOrder, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function nodeRepositoryRenumbersNodesIfNoFreeSortingIndexesAreAvailable() {
		$liveContext = new ContentContext('live');
		$rootNode = $liveContext->getWorkspace()->getRootNode();

		$liveParentNode = $rootNode->createNode('parentNode');
		$nodes = array();
		$nodes[0] = $liveParentNode->createNode('node000');
		$nodes[150] = $liveParentNode->createNode('node150');

		$this->persistenceManager->persistAll();

		for ($i = 1; $i < 150; $i++) {
			$nodes[$i] = $liveParentNode->createNode('node' . sprintf('%1$03d', $i));
			$nodes[$i]->moveAfter($nodes[$i - 1]);
		}
		$this->persistenceManager->persistAll();

		$actualChildNodes = $liveParentNode->getChildNodes();
		$this->assertSameOrder($nodes, $actualChildNodes);
	}

	/**
	 * Asserts that the order of the given nodes is the same.
	 * This doesn't check if the node objects are the same or equal but rather tests
	 * if their path is identical. Therefore nodes can be in different workspaces
	 * or proxy nodes.
	 *
	 * @param array $expectedNodes The expected order
	 * @param array $actualNodes The actual order
	 * @return void
	 */
	protected function assertSameOrder(array $expectedNodes, array $actualNodes) {
		if (count($expectedNodes) !== count($actualNodes)) {
			$this->fail(sprintf('Number of nodes did not match: got %s expected and %s actual nodes.', count($expectedNodes), count($actualNodes)));
		}

		reset($expectedNodes);
		foreach ($actualNodes as $actualNode) {
			$expectedNode = current($expectedNodes);
			if ($expectedNode->getPath() !== $actualNode->getPath()) {
				echo "\nActual node indexes:\n";
				foreach ($actualNodes as $actualNode) {
					printf("Node %s has index %s\n", $actualNode->getPath(), $actualNode->getIndex());
				}
				$this->fail(sprintf('Expected node %s (index %s), actual node %s (index %s)', $expectedNode->getName(), $expectedNode->getIndex(), $actualNode->getName(), $actualNode->getIndex()));
			}
			next($expectedNodes);
		}
		$this->assertTrue(TRUE);
	}

}