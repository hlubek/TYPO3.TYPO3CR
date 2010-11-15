<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Domain\Model;

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
 * A Node
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @entity
 * @scope prototype
 */
class Node {

	const SCAN_PATTERN_PATH = '/^\/|\/[a-zA-Z0-9][a-zA-Z0-9\-\/][a-zA-Z0-9]{0,254}$/';

	/**
	 * Absolute path of this node
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Workspace this node is contained in
	 *
	 * @var \F3\TYPO3CR\Domain\Model\Workspace
	 */
	protected $workspace;

	/**
	 * Identifier of this node which is unique within its workspace
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * Depth at which this node is located
	 *
	 * @var integer
	 */
	protected $depth;

	/**
	 * Index within the nodes with the same parent
	 *
	 * @var integer
	 */
	protected $index;

	/**
	 * Properties of this Node
	 *
	 * @var array
	 */
	protected $properties = array();

	/**
	 * An optional object which contains the content of this node
	 *
	 * @var object
	 */
	protected $contentObject;

	/**
	 * The content type of this node
	 *
	 * @var string
	 */
	protected $contentType = 'unstructured';

	/**
	 * If this is a removed node. This flag can and is only used in workspaces
	 * which do have a base workspace. In a bottom level workspace nodes are
	 * really removed, in other workspaces, removal is realized by this flag.
	 *
	 * @var boolean
	 */
	protected $removed = FALSE;

	/**
	 * @var \F3\TYPO3CR\Domain\Service\Context
	 * @transient
	 */
	protected $context;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * @inject
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructs this node
	 *
	 * @param string $path Absolute path of this node
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace The workspace this node will be contained in
	 * @param string $identifier Uuid of this node. Specifying this only makes sense while creating Corresponding Nodes
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function  __construct($path, \F3\TYPO3CR\Domain\Model\Workspace $workspace, $identifier = NULL) {
		$this->setPath($path);
		$this->workspace = $workspace;
		$this->identifier = ($identifier === NULL) ? \F3\FLOW3\Utility\Algorithms::generateUUID() : $identifier;
	}

	/**
	 * Sets the absolute path of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the path of a node manually may lead to unexpected behavior.
	 *
	 * @param string $path
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPath($path) {
		if (!is_string($path) || !preg_match(self::SCAN_PATTERN_PATH, $path)) {
			throw new \InvalidArgumentException('Invalid path: A path must be a valid string, be absolute (starting with a slash) and contain only the allowed characters.', 1284369857);
		}
		$this->path = $path;
		$this->depth = ($path === '/') ? 0 : substr_count($path, '/');
	}

	/**
	 * Returns the path of this node
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Returns the level at which this node is located.
	 * Counting starts with 0 for "/", 1 for "/foo", 2 for "/foo/bar" etc.
	 *
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDepth() {
		return $this->depth;
	}

	/**
	 * Returns the name of this node
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return ($this->path === '/') ? '' : substr($this->path, strrpos($this->path, '/') + 1);
	}

	/**
	 * Sets the workspace of this node.
	 *
	 * This method is only for internal use by the content repository. Changing
	 * the workspace of a node manually may lead to unexpected behavior.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setWorkspace(\F3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$this->workspace = $workspace;
	}

	/**
	 * Returns the workspace this node is contained in
	 *
	 * @return \F3\TYPO3CR\Domain\Model\Workspace
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getWorkspace() {
		return $this->workspace;
	}

	/**
	 * Returns the identifier of this node
	 *
	 * @return string the node's UUID (unique within the workspace)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * Sets the index of this node
	 *
	 * NOTE: This method is meant for internal use and must only be used by other nodes.
	 *
	 * @param integer $index The new index
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setIndex($index) {
		$this->index = $index;
	}

	/**
	 * Returns the index of this node which determines the order among siblings
	 * with the same parent node.
	 *
	 * @return integer
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * Returns the parent node of this node
	 *
	 * @return \F3\TYPO3CR\Domain\Model\Node The parent node or NULL if this is the root node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParent() {
		if ($this->path === '/') {
			return NULL;
		}
		$parentNodePath = substr($this->path, 0, strrpos($this->path, '/'));
		$parentNode = $this->nodeRepository->findOneByPath($parentNodePath, $this->context->getWorkspace());
		return $this->treatNodeWithContext($parentNode);
	}

	/**
	 * Moves this node before the given node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $referenceNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo finish implementation
	 * @todo make workspaces compliant
	 */
	public function moveBefore(\F3\TYPO3CR\Domain\Model\Node $referenceNode) {
		if ($this->path === '/') {
			throw new \F3\TYPO3CR\Exception\NodeException('The root node cannot be moved.', 1285005924);
		}

		$referenceNodePath = $referenceNode->getPath();
		if (substr($this->path, 0, strrpos($this->path, '/')) !== substr($referenceNodePath, 0, strrpos($referenceNodePath, '/'))) {
			throw new \F3\TYPO3CR\Exception\NodeException('Moving to other levels is currently not supported.', 1285005926);
		}

#		$rebalanceStartIndex = ($referenceNode->getIndex() < $this->index) ?
	}

	/**
	 * Sets the specified property.
	 *
	 * If the node has a content object attached, the property will be set there
	 * if it is settable.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $value Value of the property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setProperty($propertyName, $value) {
		if (is_object($this->contentObject)) {
			if (\F3\FLOW3\Reflection\ObjectAccess::isPropertySettable($this->contentObject, $propertyName)) {
				\F3\FLOW3\Reflection\ObjectAccess::setProperty($this->contentObject, $propertyName, $value);
			}
		} else {
			$this->properties[$propertyName] = $value;
		}
	}

	/**
	 * If this node has a property with the given name.
	 *
	 * If the node has a content object attached, the property will be checked
	 * there.
	 *
	 * @param string $propertyName
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasProperty($propertyName) {
		if (is_object($this->contentObject)) {
			return \F3\FLOW3\Reflection\ObjectAccess::isPropertyGettable($this->contentObject, $propertyName);
		} else {
			return isset($this->properties[$propertyName]);
		}
	}

	/**
	 * Returns the specified property.
	 *
	 * If the node has a content object attached, the property will be fetched
	 * there if it is gettable.
	 *
	 * @param string $propertyName Name of the property
	 * @return mixed value of the property
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperty($propertyName) {
		if (is_object($this->contentObject)) {
			if (\F3\FLOW3\Reflection\ObjectAccess::isPropertyGettable($this->contentObject, $propertyName)) {
				return \F3\FLOW3\Reflection\ObjectAccess::getProperty($this->contentObject, $propertyName);
			}
		} else {
			return isset($this->properties[$propertyName]) ? $this->properties[$propertyName] : NULL;
		}
	}

	/**
	 * Returns all properties of this node.
	 *
	 * If the node has a content object attached, the properties will be fetched
	 * there.
	 *
	 * @return array Property values, indexed by their name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperties() {
		if (is_object($this->contentObject)) {
			return \F3\FLOW3\Reflection\ObjectAccess::getGettableProperties($this->contentObject);
		} else {
			return $this->properties;
		}
	}

	/**
	 * Returns the names of all properties of this node.
	 *
	 * @return array Property names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyNames() {
		if (is_object($this->contentObject)) {
			return \F3\FLOW3\Reflection\ObjectAccess::getGettablePropertyNames($this->contentObject);
		} else {
			return array_keys($this->properties);
		}
	}

	/**
	 * Sets a content object for this node.
	 *
	 * @param object $contentObject The content object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentObject($contentObject) {
		if (!is_object($contentObject)) {
			throw new \InvalidArgumentException('Argument must be an object, ' . \gettype($contentObject) . ' given.', 1283522467);
		}
		$this->contentObject = $contentObject;
	}

	/**
	 * Returns the content object of this node (if any).
	 *
	 * @return object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentObject() {
		return $this->contentObject;
	}

	/**
	 * Unsets the content object of this node.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unsetContentObject() {
		$this->contentObject = NULL;
	}

	/**
	 * Sets the content type of this node.
	 *
	 * @param string $contentType
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContentType($contentType) {
		if (!$this->contentTypeManager->hasContentType($contentType)) {
			throw new \F3\TYPO3CR\Exception\NodeException('Unknown content type "' . $contentType . '".', 1285519999);
		}
		$this->contentType = $contentType;
	}

	/**
	 * Returns the content type of this node.
	 *
	 * @return string $contentType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContentType() {
		return $this->contentType;
	}

	/**
	 * Creates, adds and returns a child node of this node.
	 *
	 * @param string $name
	 * @return \F3\TYPO3CR\Domain\Model\Node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createNode($name) {
		$currentWorkspace = $this->context->getWorkspace();

		$newPath = $this->path . ($this->path !== '/' ? '/' : '') . $name;
		$newIndex = $this->nodeRepository->countByParentAndContentType($this->path, NULL, $currentWorkspace) + 1;

		$newNode = $this->objectManager->create('F3\TYPO3CR\Domain\Model\Node', $newPath, $currentWorkspace);
		$newNode->setIndex($newIndex);

		$this->nodeRepository->add($newNode);
		return $this->treatNodeWithContext($newNode);
	}

	/**
	 * Returns a node specified by the given relative path.
	 *
	 * @param string $path Path specifying the node, relative to this node
	 * @return \F3\TYPO3CR\Domain\Model\Node The specified node or NULL if no such node exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNode($path) {
		$normalizedPath = $this->normalizeRelativePath($path);
		$node = $this->nodeRepository->findOneByPath($normalizedPath, $this->context->getWorkspace());
		if (!$node) {
			return NULL;
		}
		return $this->treatNodeWithContext($node);
	}

	/**
	 * Returns the primary child node of this node.
	 *
	 * Which node acts as a primary child node will in the future depend on the
	 * content type. For now it is just the first child node.
	 *
	 * @return \F3\TYPO3CR\Domain\Model\Node The primary child node or NULL if no such node exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPrimaryChildNode() {
		$node = $this->nodeRepository->findFirstByParentAndContentType($this->path, NULL, $this->context->getWorkspace());
		if (!$node) {
			return NULL;
		}
		return $this->treatNodeWithContext($node);
	}

	/**
	 * Returns all direct child nodes of this node.
	 * If a content type is specified, only nodes of that type are returned.
	 *
	 * @param string $contentType If specified, only nodes with that content type are considered
	 * @return array<\F3\TYPO3CR\Domain\Model\Node> An array of nodes or an empty array if no child nodes matched
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildNodes($contentType = NULL) {
		$nodes = $this->nodeRepository->findByParentAndContentType($this->path, $contentType, $this->context->getWorkspace());
		return $this->treatNodesWithContext($nodes);
	}

	/**
	 * Removes this node and all its child nodes.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function remove() {
		foreach ($this->getChildNodes() as $childNode) {
			$childNode->remove();
		}

		if ($this->workspace->getBaseWorkspace() === NULL) {
			$this->nodeRepository->remove($this);
		} else {
			$this->removed = TRUE;
		}
	}

	/**
	 * If this node is a removed node.
	 *
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isRemoved() {
		return $this->removed;
	}

	/**
	 * Sets the context from which this node was acquired.
	 *
	 * This will be set by the context or other nodes while retrieving this node.
	 * This method is only for internal use, don't mess with it.
	 *
	 * @param \F3\TYPO3CR\Domain\Service\Context $context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext(\F3\TYPO3CR\Domain\Service\Context $context) {
		$this->context = $context;
	}

	/**
	 * Returns the current context this node operates in.
	 *
	 * @return \F3\TYPO3CR\Domain\Service\Context
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Normalizes the given relative path
	 *
	 * @param string $relativePath The unnormalized relative path
	 * @return string The normalized absolute path
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Properly implement relative path support
	 */
	protected function normalizeRelativePath($relativePath) {
		if ($relativePath === '.') {
			return $this->path;
		}
		if ($relativePath === './') {
			return $this->path . '/';
		}
		return $this->path . ($this->path !== '/' ? '/' : '') . $relativePath;
	}

	/**
	 *
	 * @param array $originalNodes
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function treatNodesWithContext(array $originalNodes) {
		$proxyNodes = array();
		foreach ($originalNodes as $index => $node) {
			$proxyNodes[$index] = $this->treatNodeWithContext($node);
		}
		return $proxyNodes;
	}

	/**
	 *
	 *
	 * @param mixed $node
	 * @return \F3\TYPO3CR\Domain\Model\Node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function treatNodeWithContext($node) {
		if ($node instanceof \F3\TYPO3CR\Domain\Model\Node) {
			if ($node->getWorkspace() !== $this->context->getWorkspace()) {
				$node = $this->objectManager->create('F3\TYPO3CR\Domain\Model\ProxyNode', $node);
			}
			$node->setContext($this->context);
		}
		return $node;
	}
}

?>