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
 * Tests for the Node implementation of TYPO3CR
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_TYPO3CR_NodeTest extends T3_Testing_BaseTestCase {

	/**
	 * @var T3_TYPO3CR_Node
	 */
	protected $rootNode;

	/**
	 * Set up the test environment
	 */
	public function setUp() {
		$mockRepository = $this->getMock('T3_TYPO3CR_Repository', array(), array(), '', FALSE);
		$mockStorageAccess = new T3_TYPO3CR_MockStorageAccess();
		$mockStorageAccess->rawRootNodesByWorkspace = array(
			'default' => array(
				'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
				'pid' => 0,
				'nodetype' => 0,
				'name' => ''
			)
		);
		$mockStorageAccess->rawNodesByUUIDGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d00' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'pid' => 0,
					'nodetype' => 0,
					'name' => ''
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd69507d10' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'pid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d00',
					'nodetype' => 0,
					'name' => 'Content'
				),
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					'uuid' => '96bca35d-1ef5-4a47-8b0c-0ddd68507d00',
					'pid' => '96bca35d-1ef5-4a47-8b0c-0ddd69507d10',
					'nodetype' => 0,
					'name' => 'News'
				),
			)
		);
		$mockStorageAccess->rawPropertiesByUUIDGroupedByWorkspace = array(
			'default' => array(
				'96bca35d-1ef5-4a47-8b0c-0ddd68507d00' => array(
					array(
						'name' => 'title',
						'value' => 'News about the TYPO3CR',
						'namespace' => '',
						'multivalue' => FALSE
					)
				)
			)
		);

		$this->session = new T3_TYPO3CR_Session('default', $mockRepository, $mockStorageAccess, $this->componentManager);
		$this->rootNode = $this->session->getRootNode();
	}

	/**
	 * Checks if a Node fetched by getNodeByUUID() returns the expected UUID on getUUID().
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getUUIDReturnsExpectedUUID() {
		$firstExpectedUUID = '96bca35d-1ef5-4a47-8b0c-0ddd69507d10';
		$firstNode = $this->session->getNodeByUUID($firstExpectedUUID);
		$this->assertEquals($firstExpectedUUID, $firstNode->getUUID(), 'getUUID() did not return the expected UUID.');
	
		$secondExpectedUUID = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$secondNode = $this->session->getNodeByUUID($secondExpectedUUID);
		$this->assertEquals($secondExpectedUUID, $secondNode->getUUID(), 'getUUID() did not return the expected UUID.');
	}

	/**
	 * Checks if hasProperties() works as it should.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasPropertiesWorks() {
		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals(TRUE, $node->hasProperties(), 'hasProperties() did not return TRUE for a node with properties.');

		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$this->assertEquals(FALSE, $node->hasProperties(), 'hasProperties() did not return FALSE for a node without properties.');
	}

	/**
	 * Checks if getProperties() returns the expected result.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertiesWorks() {
		$emptyNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$noProperties = $emptyNode->getProperties();
		$this->assertType('T3_phpCR_PropertyIteratorInterface', $noProperties, 'getProperties() did not return a PropertyIterator for a node without properties.');

		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$properties = $node->getProperties();
		$this->assertType('T3_phpCR_PropertyIteratorInterface', $properties, 'getProperties() did not return a PropertyIterator for a node with properties.');

		$propertyIterator = new T3_TYPO3CR_PropertyIterator;
		$this->assertEquals(0, $propertyIterator->getSize(), 'getProperties() did not return an empty PropertyIterator for a node without properties.');
		$this->assertNotEquals(1, $propertyIterator->getSize(), 'getProperties() returned an empty PropertyIterator for a node with properties.');

			// we don't compare the iterators directly here, as this hits the memory limit hard. really hard.
		$titleProperty = $this->componentManager->getComponent('T3_phpCR_PropertyInterface', 'title', 'News about the TYPO3CR', $node, FALSE, $this->session);
		$this->assertEquals($titleProperty->getString(), $properties->nextProperty()->getString(), 'getProperties() did not return the expected property.');
	}

	/**
	 * Checks if hasProperty() works with various paths
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasPropertyWorks() {
		$newsNodeUUID = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$newsTitleText = 'News about the TYPO3CR';
		$newsNode = $this->session->getNodeByUUID($newsNodeUUID);

		$this->assertTrue($newsNode->hasProperty('title'), 'Expected property was not found (1).');
		$this->assertTrue($newsNode->hasProperty('./title'), 'Expected property was not found (2).');
		$this->assertTrue($newsNode->hasProperty('../News/title'), 'Expected property was not found (3).');

		$this->assertFalse($newsNode->hasProperty('nonexistant'), 'Unxpected property was found (1).');
		$this->assertFalse($newsNode->hasProperty('./nonexistant'), 'Unexpected property wasfound (2).');
	}

	/**
	 * Checks if getProperty() works with various paths
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPropertyWorks() {
		$newsNodeUUID = '96bca35d-1ef5-4a47-8b0c-0ddd68507d00';
		$newsTitleText = 'News about the TYPO3CR';
		$newsNode = $this->session->getNodeByUUID($newsNodeUUID);

		$title = $newsNode->getProperty('title');
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (1).');

		$title = $newsNode->getProperty('./title');
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (2).');

		$title = $newsNode->getProperty('../News/title');
		$this->assertEquals($title->getString(), $newsTitleText, 'Expected property was not found (3).');
	}

	/**
	 * Checks if getPrimaryNodeType() returns a NodeType object.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPrimaryNodeTypeReturnsANodeType() {
		$this->assertType('T3_phpCR_NodeTypeInterface', $this->rootNode->getPrimaryNodeType(), 'getPrimaryNodeType() in the node did not return a NodeType object.');
	}

	/**
	 * Checks if hasNodes() works as it should.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function hasNodesWorks() {
		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$this->assertEquals(TRUE, $node->hasNodes(), 'hasNodes() did not return TRUE for a node with child nodes.');

		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals(FALSE, $node->hasNodes(), 'hasNodes() did not return FALSE for a node without child nodes.');
	}

	/**
	 * Checks if getNodes() returns the expected result.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodesWorks() {
		$leaf = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$noChildNodes = $leaf->getNodes();
		$this->assertType('T3_phpCR_NodeIteratorInterface', $noChildNodes, 'getNodes() did not return a NodeIterator for a node without child nodes.');

		$node = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$childNodes = $node->getNodes();
		$this->assertType('T3_phpCR_NodeIteratorInterface', $childNodes, 'getNodes() did not return a NodeIterator for a node with child nodes.');

		$this->assertEquals(0, $noChildNodes->getSize(), 'getNodes() did not return an empty NodeIterator for a node without child nodes.');
		$this->assertNotEquals(0, $childNodes->getSize(), 'getNodes() returned an empty NodeIterator for a node with child nodes.');

		$this->assertEquals('96bca35d-1ef5-4a47-8b0c-0ddd68507d00', $childNodes->nextNode()->getUUID(), 'getNodes() did not return the expected result for a node with child nodes.');
	}

	/**
	 * Checks if getNode() returns the expected result.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getNodeWorks() {
		$newsNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals($newsNode->getNode('../News')->getUUID(), $newsNode->getUUID(), 'getNode() did not return the expected result.');
	}

	/**
	 * Tests if getName() returns same as last name returned by getPath()
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getNameWorks() {
		$leaf = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals('News', $leaf->getName(), "getName() must be the same as the last item in the path");
	}

	/**
	 * Test if the ancestor at depth = n, where n is the depth of this
	 * item, returns this node itself.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getAncestorOfNodeDepthWorks() {
		$node = $this->rootNode->getNode('Content');
		$nodeAtDepth = $node->getAncestor($node->getDepth());
		$this->assertTrue($node->isSame($nodeAtDepth), "The ancestor of depth = n, where n is the depth of this Node must be the item itself.");
	}

	/**
	 * Test if getting the ancestor of depth = n, where n is greater than depth
	 * of this node, throws an phpCR_ItemNotFoundException for a sub node.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getAncestorOfGreaterDepthOnSubNodeThrowsException() {
		$node = $this->rootNode->getNode('Content/News');
		try {
			$node->getAncestor($node->getDepth() + 1);
			$this->fail("Getting ancestor of depth n, where n is greater than depth of this Node must throw an ItemNotFoundException");
		} catch (T3_phpCR_ItemNotFoundException $e) {
			// success
		}
	}

	/**
	 * Test if getting the ancestor of depth = n, where n is greater than depth
	 * of this node, throws an phpCR_ItemNotFoundException for a root node.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getAncestorOfGreaterDepthOnRootNodeThrowsException() {
		$node = $this->rootNode;
		try {
			$node->getAncestor($node->getDepth() + 1);
			$this->fail("Getting ancestor of depth n, where n is greater than depth of this Node must throw an ItemNotFoundException");
		} catch (T3_phpCR_ItemNotFoundException $e) {
			// success
		}
	}

	/**
	 * Test if getting the ancestor of negative depth throws an ItemNotFoundException.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getAncestorOfNegativeDepthThrowsException() {
		try {
			$this->rootNode->getAncestor(-1);
			$this->fail("Getting ancestor of depth < 0 must throw an ItemNotFoundException.");
		} catch (T3_phpCR_ItemNotFoundException $e) {
			// success
		}
	}

	/**
	 * Tests if isSame() returns FALSE when retrieving an item through different
	 * sessions
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @todo try to fetch root node through other session
	 * @test
	 */
	public function isSameReturnsTrueForSameNodes() {
			// fetch root node "by hand"
		$testNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd69507d00');
		$this->assertTrue($this->rootNode->isSame($testNode), "isSame() must return FALSE for the same item.");
	}

	/**
	 * Tests if getParent() returns parent node
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getParentReturnsExpectedNode() {
		$testNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd69507d10');
		$this->assertTrue($this->rootNode->isSame($testNode->getParent()), "getParent() of a child node does not return the parent node.");
	}

	/**
	 * Tests if getParent() of root throws an phpCR_ItemNotFoundException
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getParentOfRootFails() {
		try {
			$this->rootNode->getParent();
			$this->fail("getParent() of root must throw an ItemNotFoundException.");
		} catch (T3_phpCR_ItemNotFoundException $e) {
			// success
		}
	}

	/**
	 * Tests if depth of root is 0, depth of a sub node of root is 1, and sub-sub nodes have a depth of 2
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getDepthReturnsCorrectDepth() {
		$this->assertEquals(0, $this->rootNode->getDepth(), "getDepth() of root must be 0");

		$testNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals(2, $testNode->getDepth(), "getDepth() of subchild must be 2");

		for ($it = $this->rootNode->getNodes(); $it->hasNext(); ) {
			$this->assertEquals(1, $it->next()->getDepth(), "getDepth() of child node of root must be 1");
		}
	}

	/**
	 * Tests if getSession() is same as through which the Item was acquired
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function getSessionReturnsSourceSession() {
		$this->assertSame($this->rootNode->getSession(), $this->session, "getSession must return the Session through which the Node was acquired.");
	}

	/**
	 * Tests if isNode() returns FALSE
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @test
	 */
	public function isNodeReturnsTrue() {
		$this->assertTrue($this->rootNode->isNode(), "isNode() must return FALSE.");
	}

	/**
	 * Tests if getPath() returns the correct path.
	 *
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function getPathWithoutSameNameSiblingsWorks() {
		$testNode = $this->session->getNodeByUUID('96bca35d-1ef5-4a47-8b0c-0ddd68507d00');
		$this->assertEquals('/Content/News', $testNode->getPath(), "getPath() returns wrong result");
	}

	/**
	 * Test if addNode() returns a Node.
	 *
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeReturnsANode() {
		$newNode = $this->rootNode->addNode('User');
		$this->assertType('T3_phpCR_NodeInterface', $newNode, 'addNode() does not return an object of type T3_phpCR_NodeInterface.');
		$this->assertTrue($this->rootNode->isSame($newNode->getParent()), 'After addNode() calling getParent() from the new node does not return the expected parent node.');
	}

	/**
	 * Test if addNode(Content/./Categories/Pages/User) returns a Node.
	 *
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function addNodeWithRelPathReturnsANode() {
		$newNode1 = $this->rootNode->addNode('SomeItem');
		$newNode2 = $this->rootNode->addNode('Content/./News/SomeItem');

		$this->assertType('T3_phpCR_NodeInterface', $newNode1, 'Function: addNode() - returns not an object from type T3_phpCR_NodeInterface.');
		$this->assertType('T3_phpCR_NodeInterface', $newNode2, 'Function: addNode() - returns not an object from type T3_phpCR_NodeInterface.');

		$expectedParentNode = $this->rootNode->getNode('Content/News');
		$this->assertTrue($expectedParentNode->isSame($newNode2->getParent()), 'After addNode() calling getParent() from the new node does not return the expected parent node.');
	}

	/**
	 * Test save() on a node
	 *
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function saveWorksAsExpected() {
		$newNode1 = $this->rootNode->addNode('User');
		$newNode2 = $this->rootNode->addNode('Content/News/User');

		$this->rootNode->save();

		$newNode1 = $this->session->getNodeByUUID($newNode1->getUUID());
		$this->assertType('T3_phpCR_NodeInterface', $newNode1, 'Function: save() - Nodes are not persisted in the CR.');
		$newNode2 = $this->session->getNodeByUUID($newNode2->getUUID());
		$this->assertType('T3_phpCR_NodeInterface', $newNode2, 'Function: save() - Nodes are not persisted in the CR.');
	}

	/**
	 * Test set property
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 */
	public function setPropertyCreatesPropertyAsExpected() {
		$testPropertyNode = $this->rootNode->addNode('TestPropertyNode');
		$testPropertyNode->setProperty('title', 'YEAH, it works!');
		$this->assertEquals($this->session->getItem('/TestPropertyNode/title')->getString(), 'YEAH, it works!', 'Transient storage does not work, title should be "YEAH, it works!", but is "' . $this->session->getItem('/TestPropertyNode/title')->getString() . '"');
		$this->assertTrue($this->session->getItem('/TestPropertyNode/title')->isNew(), 'isNew() not correctly set. (Needs to be TRUE)');
		$testPropertyNode->save();
		$this->assertFalse($this->session->getItem('/TestPropertyNode/title')->isNew(), 'isNew() not correctly set. (Needs to be FALSE)');

		$testPropertyNode->setProperty('title', 'YEAH, it still works!');
		$this->assertEquals($this->session->getItem('/TestPropertyNode/title')->getString(), 'YEAH, it still works!', 'Transient storage does not work, title should be "YEAH, it still works!", but is "' . $this->session->getItem('/TestPropertyNode/title')->getString() . '"');
		$this->assertTrue($this->session->getItem('/TestPropertyNode/title')->isModified(), 'isModified() not correctly set. (Needs to be TRUE)');
		$testPropertyNode->save();
		$this->assertFalse($this->session->getItem('/TestPropertyNode/title')->isModified(), 'isModified() not correctly set. (Needs to be FALSE)');
	}
}
?>
