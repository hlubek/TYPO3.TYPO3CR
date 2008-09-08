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
 * @version $Id$
 */

/**
 * The Node represents a node in the hierarchy that makes up the repository.
 *
 * @package TYPO3CR
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_TYPO3CR_Node extends F3_TYPO3CR_AbstractItem implements F3_PHPCR_NodeInterface {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var F3_PHPCR_NodeType_NodeTypeInterface
	 */
	protected $nodeType;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var array of identifiers
	 */
	protected $nodes = array();

	/**
	 * Constructs a Node
	 *
	 * @param F3_TYPO3CR_SessionInterface $session
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(array $rawData = array(), F3_PHPCR_SessionInterface $session, F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->session = $session;
		$this->componentFactory = $componentFactory;

		if (isset($rawData['newidentifier'])) {
			$this->identifier = $rawData['newidentifier'];
			$this->session->registerNodeAsNew($this);
		} elseif (!isset($rawData['identifier'])) {
			$this->identifier = F3_FLOW3_Utility_Algorithms::generateUUID();
			$this->session->registerNodeAsNew($this);
		}

		foreach ($rawData as $key => $value) {
			switch ($key) {
				case 'identifier':
					$this->identifier = $value;
					break;
				case 'parent':
					if ($value == '') {
						$this->parentNode = NULL;
					} else {
						$this->parentNode = $value;
					}
					break;
				case 'name':
					$this->name = $value;
					break;
				case 'nodetype':
					$this->nodeType = $this->componentFactory->getComponent('F3_PHPCR_NodeType_NodeTypeInterface', $value);
					break;
			}
		}

		$this->initializeProperties();
		$this->initializeNodes();
	}

	/**
	 * Fetches the properties of the node from the storage layer
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeProperties() {
		$rawProperties = $this->session->getStorageBackend()->getRawPropertiesOfNode($this->getIdentifier());
		if (is_array($rawProperties)) {
			foreach ($rawProperties as $rawProperty) {
				$property = $this->componentFactory->getComponent('F3_PHPCR_PropertyInterface', $rawProperty['name'], $rawProperty['value'], $rawProperty['type'], $this, $this->session);
				$this->properties[$property->getName()] = $property;
			}
		}
	}

	/**
	 * Fetches the properties of the node from the storage layer
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeNodes() {
		$rawNodeIdentifiers = $this->session->getStorageBackend()->getIdentifiersOfSubNodesOfNode($this->getIdentifier());
		if (is_array($rawNodeIdentifiers)) {
			$this->nodes = $rawNodeIdentifiers;
		}
	}



	/**
	 * Returns true if this is a new item, meaning that it exists only in
	 * transient storage on the Session and has not yet been saved. Within a
	 * transaction, isNew on an Item may return false (because the item has
	 * been saved) even if that Item is not in persistent storage (because the
	 * transaction has not yet been committed).
	 *
	 * Note that if an item returns true on isNew, then by definition is parent
	 * will return true on isModified.
	 *
	 * @return boolean TRUE if this item is new; FALSE otherwise.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isNew() {
		return $this->session->isRegisteredAsNewNode($this);
	}

	/**
	 * Returns true if this Item has been saved but has subsequently been
	 * modified through the current session and therefore the state of this
	 * item as recorded in the session differs from the state of this item as
	 * saved. Within a transaction, isModified on an Item may return false
	 * (because the Item has been saved since the modification) even if the
	 * modification in question is not in persistent storage (because the
	 * transaction has not yet been committed).
	 *
	 * @return boolean TRUE if this item is modified; FALSE otherwise.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isModified() {
		return $this->session->isRegisteredAsDirtyNode($this);
	}

	/**
	 * Returns FALSE if this Item is a Node; returns FALSE if this Item is a
	 * Property.
	 *
	 * @return boolean
	 * @author Ronny Unger <ru@php-workx.de>
	 */
	public function isNode() {
		return TRUE;
	}

	/**
	 * Returns the path of this node.
	 *
	 * The default implementation recursively calls this method on the
	 * parent node and appends the name and optionally the index of this
	 * node to construct the full path. Returns "/" if the parent node is
	 * not available (i.e. this is the root node).
	 *
	 * @return string
	 * @author Ronny Unger <ru@php-workx.de>
	 * @todo add support for same name siblings
	 */
	public function getPath() {
		if ($this->parentNode === NULL) {
			return '/';
		} else {
			$buffer = $this->getParent()->getPath();
			if (F3_PHP6_Functions::strlen($buffer) > 1) {
				$buffer .= '/';
			}

			$buffer .= $this->getName();
			return $buffer;
		}
	}

	/**
	 * Returns the parent of this Node.
	 *
	 * An F3_PHPCR_ItemNotFoundException is thrown if there is no parent node. This
	 * only happens if this item is the root node of a workspace.
	 *
	 * An F3_PHPCR_AccessDeniedException is thrown if the current session does not
	 * have sufficient access permissions to retrieve the parent of this item.
	 *
	 * A F3_PHPCR_RepositoryException is thrown if another error occurs.
	 *
	 * @return F3_PHPCR_NodeInterface
	 * @throws F3_PHPCR_ItemNotFoundException
	 * @throws F3_PHPCR_AccessDeniedException
	 * @throws F3_PHPCR_RepositoryException
	 * @author Ronny Unger <ru@php-workx.de>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getParent() {
		if ($this->parentNode === NULL) throw new F3_PHPCR_ItemNotFoundException("root node does not have a parent", 1187530879);

			// when instanciating we lazily store the identifier of the parent
		if ($this->parentNode instanceof F3_PHPCR_NodeInterface) {
			return $this->parentNode;
		} else {
			$this->parentNode = $this->session->getNodeByIdentifier($this->parentNode);
			return $this->parentNode;
		}
	}

	/**
	 * Delete the item
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove() {
		if ($this->parentNode === NULL) {
			throw new F3_PHPCR_NodeType_ConstraintViolationException('The root node is mandatory', 1213960971);
		}

		foreach ($this->nodes as $node) {
			$this->session->getNodeByIdentifier($node)->remove();
		}

		foreach ($this->properties as $property) {
			$property->remove();
		}

		$this->session->registerNodeAsRemoved($this);
		$this->getParent()->removeNode($this->getIdentifier());
	}

	/**
	 * Removes the given node from the internal $nodes array
	 *
	 * @param string $nodeIdentifier
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeNode($nodeIdentifier) {
		unset($this->nodes[array_search($nodeIdentifier, $this->nodes)]);
	}

	/**
	 * If keepChanges is false, this method discards all pending changes
	 * currently recorded in this Session that apply to this Item or any
	 * of its descendants (that is, the subtree rooted at this Item) and
	 * returns all items to reflect the current saved state. Outside a
	 * transaction this state is simple the current state of persistent
	 * storage. Within a transaction, this state will reflect persistent
	 * storage as modified by changes that have been saved but not yet
	 * committed.
	 * If keepChanges is true then pending change are not discarded but
	 * items that do not have changes pending have their state refreshed
	 * to reflect the current saved state, thus revealing changes made by
	 * other sessions.
	 *
	 * @param boolean $keepChanges a boolean
	 * @return void
	 * @throws InvalidItemStateException if this Item object represents a workspace item that has been removed (either by this session or another).
	 * @throws RepositoryException if another error occurs.
	 */
	public function refresh($keepChanges) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212577830);
	}

	/**
	 * Creates a new node at relPath. The new node will only be persisted on
	 * save() if it meets the constraint criteria of the parent node's node
	 * type.
	 * In order to save a newly added node, save must be called either on the
	 * Session, or on the new node's parent or higher-order ancestor (grandparent,
	 * etc.). An attempt to call save only on the newly added node will throw a
	 * RepositoryException.
	 *
	 * In the context of this method the relPath provided must not have an index
	 * on its final element. If it does then a RepositoryException is thrown.
	 *
	 * Strictly speaking, the parameter is actually a relative path to the parent
	 * node of the node to be added, appended with the name desired for the new
	 * node (if the a node is being added directly below this node then only the
	 * name need be specified). It does not specify a position within the child
	 * node ordering. If ordering is supported by the node type of the parent node
	 * then the new node is appended to the end of the child node list.
	 *
	 * The new node's primary node type will be determined (either immediately
	 * or on save, depending on the implementation) by the child node definitions
	 * in the node types of its parent, unless primaryNodeTypeName is given.
	 *
	 * @param string $relPath The path of the new node to be created.
	 * @param string $primaryNodeTypeName The name of the primary node type of the new node.
	 * @return F3_PHPCR_NodeInterface The node that was added.
	 * @throws F3_PHPCR_ItemExistsException if an item at the specified path already exists, same-name siblings are not allowed and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_PathNotFoundException if the specified path implies intermediary Nodes that do not exist or the last element of relPath has an index, and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_ConstraintViolationException if a node type or implementation-specific constraint is violated or if an attempt is made to add a node as the child of a property and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Version_VersionException if the node to which the new child is being added is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the addition of the node and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_RepositoryException If the last element of relPath has an index or if another error occurs.
	 * @author Thomas Peterson <info@thomas-peterson.de>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Many :)
	 */
	public function addNode($relPath, $primaryNodeTypeName = NULL, $identifier = NULL) {
		if ($relPath === NULL) {
			throw new F3_PHPCR_PathNotFoundException('Path not found or not provided', 1187531979);
		}

		list($lastNodeName, $remainingPath, $numberOfElementsRemaining) = F3_TYPO3CR_PathParser::getLastPathPart($relPath);

		if ($numberOfElementsRemaining===0) {
			$rawData = array(
				'parent' => $this->getIdentifier(),
				'name' => $lastNodeName,
				'nodetype' => $primaryNodeTypeName === NULL ? 'nt:base' : $primaryNodeTypeName
			);

			if ($identifier !== NULL) {
				if ($this->session->hasIdentifier($identifier)) {
					throw new F3_PHPCR_ItemExistsException('The identifier requested is already in use.', 1219424096);
				}
				$rawData['newidentifier'] = $identifier;
			}

			$newNode = $this->componentFactory->getComponent('F3_PHPCR_NodeInterface', $rawData, $this->session);

			$this->nodes[] = $newNode->getIdentifier();
			$this->session->registerNodeAsDirty($this);
		} else {
			$upperNode = F3_TYPO3CR_PathParser::parsePath($remainingPath, $this);
			$newNode = $upperNode->addNode($lastNodeName, $primaryNodeTypeName, $identifier);
		}

		return $newNode;
	}

	/**
	 * If this node supports child node ordering, this method inserts the child
	 * node at srcChildRelPath before its sibling, the child node at
	 * destChildRelPath, in the child node list.
	 * To place the node srcChildRelPath at the end of the list, a destChildRelPath
	 * of null is used.
	 *
	 * Note that (apart from the case where destChildRelPath is null) both of
	 * these arguments must be relative paths of depth one, in other words they
	 * are the names of the child nodes, possibly suffixed with an index.
	 *
	 * If srcChildRelPath and destChildRelPath are the same, then no change is
	 * made.
	 *
	 * Changes to ordering of child nodes are persisted on save of the parent
	 * node.
	 *
	 * @param string $srcChildRelPath the relative path to the child node (that is, name plus possible index) to be moved in the ordering
	 * @param string $destChildRelPath the the relative path to the child node (that is, name plus possible index) before which the node srcChildRelPath will be placed.
	 * @return void
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException  if ordering is not supported.
	 * @throws F3_PHPCR_ConstraintViolationException if an implementation-specific ordering restriction is violated and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_ItemNotFoundException if either parameter is not the relative path of a child node of this node.
	 * @throws F3_PHPCR_Version_VersionException if this node is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the re-ordering and this implementation performs this validation immediately instead of waiting until save..
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function orderBefore($srcChildRelPath, $destChildRelPath) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667765);
	}

	/**
	 * Sets the specified (single-value) property of this node to the specified
	 * value. If the property does not yet exist, it is created. The property type
	 * of the property will be that specified by the node type of this node.
	 * If, based on the name and value passed, there is more than one property
	 * definition that applies, the repository chooses one definition according
	 * to some implementation-specific criteria. Once property with name P has
	 * been created, the behavior of a subsequent setProperty(P,V) may differ
	 * across implementations. Some repositories may allow P to be dynamically
	 * re-bound to a different property definition (based for example, on the
	 * new value being of a different type than the original value) while other
	 * repositories may not allow such dynamic re-binding.
	 *
	 * If the property type of the supplied Value object is different from that
	 * required, then a best-effort conversion is attempted.
	 *
	 * If the node type of this node does not indicate a specific property type,
	 * then the property type of the supplied Value object is used and if the
	 * property already exists it assumes both the new value and new property type.
	 *
	 * Passing a null as the second parameter removes the property. It is equivalent
	 * to calling remove on the Property object itself. For example,
	 * N.setProperty("P", (Value)null) would remove property called "P" of the
	 * node in N.
	 *
	 * To save the addition or removal of a property, a save call must be
	 * performed that includes the parent of the property in its scope, that is,
	 * a save on either the session, this node, or an ancestor of this node. To
	 * save a change to an existing property, a save call that includes that
	 * property in its scope is required. This means that in addition to the
	 * above-mentioned save options, a save on the changed property itself will
	 * also work.
	 *
	 * Have a look at the JSR-283 spec and/or API documentation for more details
	 * on what is supposed to happen for different type of values being passed
	 * to this method.
	 *
	 * @param string $name The name of a property of this node
	 * @param mixed $value The value to be assigned
	 * @param integer $type The type to set for the property
	 * @return F3_PHPCR_PropertyInterface The updated Property object
	 * @throws F3_PHPCR_ValueFormatException if value cannot be converted to the type of the specified property or if the property already exists and is multi-valued.
	 * @throws F3_PHPCR_Version_VersionException if this node is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Lock_LockException  if a lock prevents the setting of the property and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_ConstraintViolationException if the change would violate a node-type or other constraint and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_RepositoryException  if another error occurs.
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setProperty($name, $value, $type = F3_PHPCR_PropertyType::UNDEFINED) {
		if ($this->hasProperty($name)) {
			if ($value === NULL) {
				$this->session->registerPropertyAsRemoved($this->properties[$name]);
				unset($this->properties[$name]);
			} else {
				if (is_array($value)) {
					$value = F3_TYPO3CR_Utility::removeNullFromArray($value);
				}
				$this->properties[$name]->setValue($value);
				$this->session->registerPropertyAsDirty($this->properties[$name]);
			}
			$this->session->registerNodeAsDirty($this);
		} elseif ($value !== NULL) {
			if (is_array($value)) {
				$value = F3_TYPO3CR_Utility::removeNullFromArray($value);
			}
			$this->properties[$name] = $this->componentFactory->getComponent('F3_PHPCR_PropertyInterface', $name, $value, $type, $this, $this->session);
			$this->session->registerPropertyAsNew($this->properties[$name]);
			$this->session->registerNodeAsDirty($this);
		}
	}

	/**
	 * Returns the node at relPath relative to this node.
	 * If relPath contains a path element that refers to a node with same-name
	 * sibling nodes without explicitly including an index using the array-style
	 * notation ([x]), then the index [1] is assumed (indexing of same name
	 * siblings begins at 1, not 0, in order to preserve compatibility with XPath).
	 *
	 * Within the scope of a single Session object, if a Node object has been
	 * acquired, any subsequent call of getNode reacquiring the same node must
	 * return a Node object reflecting the same state as the earlier Node object.
	 * Whether this object is actually the same Node instance, or simply one
	 * wrapping the same state, is up to the implementation.
	 *
	 * @param string $relPath The relative path of the node to retrieve.
	 * @return F3_PHPCR_NodeInterface The node at relPath.
	 * @throws F3_PHPCR_PathNotFoundException If no node exists at the specified path or the current Session does not read access to the node at the specified path.
	 * @throws F3_PHPCR_RepositoryException  If another error occurs.
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function getNode($relPath) {
		return F3_TYPO3CR_PathParser::parsePath($relPath, $this);
	}

	/**
	 * Gets all child nodes of this node accessible through the current Session
	 * that match namePattern (if no pattern is given, all accessible child nodes
	 * are returned). Does not include properties of this Node. The pattern may
	 * be a full name or a partial name with one or more wildcard characters ("*"),
	 * or a disjunction (using the "|" character to represent logical OR) of these.
	 * For example,
	 * N.getNodes("jcr:* | myapp:report | my doc")
	 * would return a NodeIterator holding all accessible child nodes of N that
	 * are either called 'myapp:report', begin with the prefix 'jcr:' or are
	 * called 'my doc'.
	 *
	 * Note that leading and trailing whitespace around a disjunct is ignored,
	 * but whitespace within a disjunct forms part of the pattern to be matched.
	 *
	 * The EBNF for namePattern is:
	 *
	 * namePattern ::= disjunct {'|' disjunct}
	 * disjunct ::= name [':' name]
	 * name ::= '*' | ['*'] fragment {'*' fragment} ['*']
	 * fragment ::= char {char}
	 * char ::= nonspace | ' '
	 * nonspace ::= Any XML Char (See http://www.w3.org/TR/REC-xml/) except:
	 *    '/', ':', '[', ']', '*', ''', '"', '|' or any whitespace character
	 *
	 * The pattern is matched against the names (not the paths) of the immediate
	 * child nodes of this node.
	 *
	 * If this node has no accessible matching child nodes, then an empty
	 * iterator is returned.
	 *
	 * The same reacquisition semantics apply as with getNode(String).
	 *
	 * @param string $namePattern a name pattern
	 * @return F3_PHPCR_NodeIteratorInterface a NodeIterator over all (matching) child Nodes
	 * @throws F3_PHPCR_RepositoryException  If an unexpected error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getNodes($namePattern = NULL) {
		if ($namePattern !== NULL) throw new F3_PHPCR_RepositoryException('Support for name patterns in getNodes() is not yet implemented.', 1184868411);

		$nodes = array();
		foreach ($this->nodes as $identifier) {
			$nodes[] = $this->session->getNodeByIdentifier($identifier);
		}

		return $this->componentFactory->getComponent('F3_PHPCR_NodeIteratorInterface', $nodes);
	}

	/**
	 * Returns the property at relPath relative to this node. The same
	 * reacquisition semantics apply as with getNode(String).
	 *
	 * @param string $relPath The relative path of the property to retrieve.
	 * @return F3_PHPCR_PropertyInterface The property at relPath.
	 * @throws F3_PHPCR_PathNotFoundException If no property exists at the specified path.
	 * @throws F3_PHPCR_RepositoryException  If another error occurs.
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getProperty($relPath) {
		if (F3_PHP6_Functions::strpos($relPath, '/') === FALSE && isset($this->properties[$relPath])) {
			return $this->properties[$relPath];
		} else {
			return F3_TYPO3CR_PathParser::parsePath($relPath, $this, F3_TYPO3CR_PathParser::SEARCH_MODE_PROPERTIES);
		}
	}

	/**
	 * Gets all properties of this node accessible through the current Session
	 * that match namePattern (if no pattern is given, all accessible properties
	 * are returned). Does not include child nodes of this node. The pattern may
	 * be a full name or a partial name with one or more wildcard characters ("*"),
	 * or a disjunction (using the "|" character to represent logical OR) of
	 * these. For example,
	 * N.getProperties("jcr:* | myapp:name | my doc")
	 * would return a PropertyIterator holding all accessible properties of N
	 * that are either called 'myapp:name', begin with the prefix 'jcr:' or are
	 * called 'my doc'.
	 *
	 * Note that leading and trailing whitespace around a disjunct is ignored,
	 * but whitespace within a disjunct forms part of the pattern to be matched.
	 *
	 * The EBNF for namePattern is:
	 *
	 * namePattern ::= disjunct {'|' disjunct}
	 * disjunct ::= name [':' name]
	 * name ::= '*' | ['*'] fragment {'*' fragment} ['*']
	 * fragment ::= char {char}
	 * char ::= nonspace | ' '
	 * nonspace ::= Any XML Char (See http://www.w3.org/TR/REC-xml/)
	 *    except: '/', ':', '[', ']', '*', ''', '"', '|' or any whitespace character
	 *
	 * The pattern is matched against the names (not the paths) of the immediate
	 * child properties of this node.
	 *
	 * If this node has no accessible matching properties, then an empty iterator
	 * is returned.
	 *
	 * The same reacquisition semantics apply as with getNode(String).
	 *
	 * @param string $namePattern a name pattern
	 * @return F3_PHPCR_PropertyIteratorInterface a PropertyIterator
	 * @throws F3_PHPCR_RepositoryException  If an unexpected error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implement support for $namePattern
	 */
	public function getProperties($namePattern = NULL) {
		if ($namePattern !== NULL) throw new F3_PHPCR_RepositoryException('Support for name patterns in getProperties() is not yet implemented.', 1183463152);

		return $this->componentFactory->getComponent('F3_PHPCR_PropertyIteratorInterface', $this->properties);
	}

	/**
	 * Returns the primary child item of this node. The primary node type of this
	 * node may specify one child item (child node or property) of this node as
	 * the primary child item. This method returns that item.
	 * In cases where the primary child item specifies the name of a set same-name
	 * sibling child nodes, the node returned will be the one among the same-name
	 * siblings with index [1].
	 *
	 * The same reacquisition semantics apply as with getNode(String).
	 *
	 * @return F3_PHPCR_ItemInterface the primary child item.
	 * @throws F3_PHPCR_ItemNotFoundException if this node does not have a primary child item, either because none is declared in the node type or because a declared primary item is not present on this node instance, or not accessible through the current Session
	 * @throws F3_PHPCR_RepositoryException  if another error occurs.
	 */
	public function getPrimaryItem() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667766);
	}

	/**
	 * Returns the identifier of this node. Applies to both referenceable and
	 * non-referenceable nodes.
	 *
	 * @return string the identifier of this node
	 * @throws F3_PHPCR_RepositoryException If an error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Check for mix:referenceable on node to determine if an UnsupportedRepositoryOperationException should be thrown. Then throw RepositoryException if still no Identifier is available.
	 */
	public function getIdentifier() {
		if (isset($this->identifier)) {
			return $this->identifier;
		} else {
			throw new F3_PHPCR_UnsupportedRepositoryOperationException('Node has no Identifier', 1181070099);
		}
	}

	/**
	 * This method returns the index of this node within the ordered set of its
	 * same-name sibling nodes. This index is the one used to address same-name
	 * siblings using the square-bracket notation, e.g., /a[3]/b[4]. Note that
	 * the index always starts at 1 (not 0), for compatibility with XPath. As a
	 * result, for nodes that do not have same-name-siblings, this method will
	 * always return 1.
	 *
	 * @return integer The index of this node within the ordered set of its same-name sibling nodes.
	 * @throws F3_PHPCR_RepositoryException  if an error occurs.
	 */
	public function getIndex() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667767);
	}

	/**
	 * This method returns all REFERENCE properties that refer to this node, have
	 * the specified name and that are accessible through the current Session.
	 * If the name parameter is null then all referring REFERENCES are returned
	 * regardless of name.
	 *
	 * Some level 2 implementations may only return properties that have been
	 * saved (in a transactional setting this includes both those properties that
	 * have been saved but not yet committed, as well as properties that have been
	 * committed). Other level 2 implementations may additionally return properties
	 * that have been added within the current Session but are not yet saved.
	 *
	 * In implementations that support versioning, this method does not return
	 * properties that are part of the frozen state of a version in version storage.
	 *
	 * If this node has no referring properties with the specified name, an empty
	 * iterator is returned.
	 *
	 * @param string $name name of referring REFERENCE properties to be returned; if null then all referring REFERENCEs are returned
	 * @return F3_PHPCR_PropertyIteratorInterface A PropertyIterator.
	 * @throws F3_PHPCR_RepositoryException  if an error occurs
	 * @author Matthias Hoermann <hoermann@saltation.de>
	 */
	public function getReferences($name = NULL) {
		$rawReferences = $this->session->getStorageBackend()->getRawPropertiesOfTypedValue($name, F3_PHPCR_PropertyType::REFERENCE, $this->getIdentifier());
		$references = array();
		if (is_array($rawReferences)) {
			foreach ($rawReferences as $rawReference) {
				$reference = $this->componentFactory->getComponent('F3_PHPCR_PropertyInterface', $rawReference['name'], $rawReference['value'], $rawReference['type'], $this, $this->session);
				$references[$reference->getName()] = $reference;
			}
		}
		return $this->componentFactory->getComponent('F3_PHPCR_PropertyIteratorInterface', $references);
	}

	/**
	 * This method returns all WEAKREFERENCE properties that refer to this node,
	 * have the specified name and that are accessible through the current Session.
	 * If the name parameter is null then all referring WEAKREFERENCE are returned
	 * regardless of name.
	 *
	 * Some level 2 implementations may only return properties that have been
	 * saved (in a transactional setting this includes both those properties that
	 * have been saved but not yet committed, as well as properties that have
	 * been committed). Other level 2 implementations may additionally return
	 * properties that have been added within the current Session but are not yet
	 * saved.
	 *
	 * In implementations that support versioning, this method does not return
	 * properties that are part of the frozen state of a version in version storage.
	 *
	 * If this node has no referring properties with the specified name, an empty
	 * iterator is returned.
	 *
	 * @param string $name name of referring WEAKREFERENCE properties to be returned; if null then all referring WEAKREFERENCEs are returned
	 * @return F3_PHPCR_PropertyIteratorInterface A PropertyIterator.
	 * @throws F3_PHPCR_RepositoryException  if an error occurs
	 * @author Matthias Hoermann <hoermann@saltation.de>

	 */
	public function getWeakReferences($name = NULL) {
		$rawReferences = $this->session->getStorageBackend()->getRawPropertiesOfTypedValue($name, F3_PHPCR_PropertyType::WEAKREFERENCE, $this->getIdentifier());
		$references = array();
		if (is_array($rawReferences)) {
			foreach ($rawReferences as $rawReference) {
				$reference = $this->componentFactory->getComponent('F3_PHPCR_PropertyInterface', $rawReference['name'], $rawReference['value'], $rawReference['type'], $this, $this->session);
				$references[$reference->getName()] = $reference;
			}
		}
		return $this->componentFactory->getComponent('F3_PHPCR_PropertyIteratorInterface', $references);
	}

	/**
	 * Indicates whether a node exists at relPath Returns true if a node accessible
	 * through the current Session exists at relPath and false otherwise.
	 *
	 * @param string $relPath The path of a (possible) node.
	 * @return boolean true if a node exists at relPath; false otherwise.
	 * @throws F3_PHPCR_RepositoryException If an unspecified error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Implement without actually getting the node(s)
	 */
	public function hasNode($relPath) {
		try {
			$this->getNode($relPath);
		} catch (F3_PHPCR_PathNotFoundException $e) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Indicates whether a property exists at relPath Returns true if a property
	 * accessible through the current Session exists at relPath and false otherwise.
	 *
	 * @param string $relPath The path of a (possible) property.
	 * @return boolean true if a property exists at relPath; false otherwise.
	 * @throws F3_PHPCR_RepositoryException If an unspecified error occurs.
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasProperty($relPath) {
		if (F3_PHP6_Functions::strpos($relPath, '/') === FALSE) {
			return isset($this->properties[$relPath]);
		} else {
			try {
				$this->getProperty($relPath);
				return TRUE;
			} catch (F3_PHPCR_PathNotFoundException $e) {
				return FALSE;
			}
		}
	}

	/**
	 * Indicates whether this node has child nodes. Returns true if this node has
	 * one or more child nodes accessible through the current Session; false otherwise.
	 *
	 * @return boolean true if this node has one or more child nodes; false otherwise.
	 * @throws F3_PHPCR_RepositoryException  If an unspecified error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasNodes() {
		return count($this->nodes) > 0;
	}

	/**
	 * Indicates whether this node has properties. Returns true if this node has
	 * one or more properties accessible through the current Session; false otherwise.
	 *
	 * @return boolean true if this node has one or more properties; false otherwise.
	 * @throws F3_PHPCR_RepositoryException  If an unspecified error occurs.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasProperties() {
		return count($this->properties) > 0;
	}

	/**
	 * Returns the primary node type in effect for this node. Note that this may
	 * differ from the node type implied by the node's jcr:primaryType property
	 * if that property has recently been created or changed and has not yet been
	 * saved. Which NodeType is returned when this method is called on the root
	 * node of a workspace is up to the implementation, though the returned type
	 * must, of course, be consistent with the child nodes and properties of the
	 * root node.
	 *
	 * @return F3_PHPCR_NodeType_NodeTypeInterface a NodeType object.
	 * @throws F3_PHPCR_RepositoryException  if an error occurs
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPrimaryNodeType() {
		return $this->nodeType;
	}

	/**
	 * Returns an array of NodeType objects representing the mixin node types in
	 * effect for this node. This includes only those mixin types explicitly
	 * assigned to this node. It does not include mixin types inherited through
	 * the addition of supertypes to the primary type hierarchy or through the
	 * addition of supertypes to the type hierarchy of any of the declared mixin
	 * types. Note that this may differ from the node types implied by the node's
	 * jcr:mixinTypes property if that property has recently been created or
	 * changed and has not yet been saved.
	 *
	 * @return array of F3_PHPCR_NodeType_NodeTypeInterface objects.
	 * @throws F3_PHPCR_RepositoryException  if an error occurs
	 */
	public function getMixinNodeTypes() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667711);
	}

	/**
	 * Returns true if this node is of the specified primary node type or mixin
	 * type, or a subtype thereof. Returns false otherwise.
	 * This method respects the effective node type of the node. Note that this
	 * may differ from the node type implied by the node's jcr:primaryType property
	 * or jcr:mixinTypes property if that property has recently been created or
	 * changed and has not yet been saved.
	 *
	 * @param string $nodeTypeName the name of a node type.
	 * @return boolean true if this node is of the specified primary node type or mixin type, or a subtype thereof. Returns false otherwise.
	 * @throws F3_PHPCR_RepositoryException  If an error occurs.
	 */
	public function isNodeType($nodeTypeName) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667712);
	}

	/**
	 * Changes the primary node type of this node to nodeTypeName. Also immediately
	 * changes this node's jcr:primaryType property appropriately. Semantically,
	 * the new node type may take effect immediately and must take effect on save.
	 * Whichever behavior is adopted it must be the same as the behavior adopted
	 * for addMixin() (see below) and the behavior that occurs when a node is
	 * first created.
	 * If the presence of an existing property or child node would cause an
	 * incompatibility with the new node type a ConstraintViolationException is
	 * thrown either immediately or on save.
	 *
	 * @param string $nodeTypeName the name of the new node type.
	 * @return void
	 * @throws F3_PHPCR_ConstraintViolationException If the specified primary node type is prevented from being assigned.
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException If the specified nodeTypeName is not recognized and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Version_VersionException if this node is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the change of the primary node type and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function setPrimaryType($nodeTypeName) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667713);
	}

	/**
	 * Adds the specified mixin node type to this node and adds mixinName to this
	 * node's jcr:mixinTypes property. Semantically, the new node type may take
	 * effect immediately and must take effect on save. Whichever behavior is adopted
	 * it must be the same as the behavior adopted for setPrimaryType(java.lang.String)
	 * and the behavior that occurs when a node is first created.
	 * A ConstraintViolationException is thrown either immediately or on save if
	 * a conflict with another assigned mixin or the primary node type or for an
	 * implementation-specific reason. Implementations may differ on when this
	 * validation is done.
	 *
	 * @param string $mixinName the name of the mixin node type to be added
	 * @return void
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException If the specified mixinName is not recognized and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_ConstraintViolationException If the specified mixin node type is prevented from being assigned.
	 * @throws F3_PHPCR_Version_VersionException if this node is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save..
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the addition of the mixin and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function addMixin($mixinName) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667714);
	}

	/**
	 * Removes the specified mixin node type from this node and removes mixinName
	 * from this node's jcr:mixinTypes property. Both the semantic change in
	 * effective node type and the persistence of the change to the jcr:mixinTypes
	 * property occur on save.
	 *
	 * @param string $mixinName the name of the mixin node type to be removed.
	 * @return void
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException if the specified mixinName is not currently assigned to this node and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_ConstraintViolationException if the specified mixin node type is prevented from being removed and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Version_VersionException if this node is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the removal of the mixin and this implementation performs this validation immediately instead of waiting until save..
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function removeMixin($mixinName) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667715);
	}

	/**
	 * Returns true if the specified mixin node type, mixinName, can be added to
	 * this node. Returns false otherwise. A result of false must be returned in
	 * each of the following cases:
	 * * The mixin's definition conflicts with an existing primary or mixin node
	 *   type of this node.
	 * * This node is versionable and checked-in or is non-versionable and its
	 *   nearest versionable ancestor is checked-in.
	 * * This node is protected (as defined in this node's NodeDefinition, found
	 *   in the node type of this node's parent).
	 * * An access control restriction would prevent the addition of the mixin.
	 * * A lock would prevent the addition of the mixin.
	 * * An implementation-specific restriction would prevent the addition of the mixin.
	 *
	 * @param string $mixinName The name of the mixin to be tested.
	 * @return boolean true if the specified mixin node type, mixinName, can be added to this node; false otherwise.
	 * @throws F3_PHPCR_NodeType_NoSuchNodeTypeException if the specified mixin node type name is not recognized.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function canAddMixin($mixinName) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667716);
	}

	/**
	 * Returns the node definition that applies to this node. In some cases there
	 * may appear to be more than one definition that could apply to this node.
	 * However, it is assumed that upon creation of this node, a single particular
	 * definition was used and it is that definition that this method returns.
	 * How this governing definition is selected upon node creation from among
	 * others which may have been applicable is an implementation issue and is
	 * not covered by this specification. The NodeDefinition returned when this
	 * method is called on the root node of a workspace is also up to the
	 * implementation.
	 *
	 * @return F3_PHPCR_NodeType_NodeDefinitionInterface a NodeDefinition object.
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getDefinition() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667717);
	}

	/**
	 * Creates a new version with a system generated version name and returns
	 * that version (which will be the new base version of this node). Sets the
	 * jcr:checkedOut property to false thus putting the node into the checked-in
	 * state. This means that this node and its connected non-versionable subtree
	 * become read-only. A node's connected non-versionable subtree is the set of
	 * non-versionable descendant nodes reachable from that node through child
	 * links without encountering any versionable nodes. In other words, the read-only
	 * status flows down from the checked-in node along every child link until
	 * either a versionable node is encountered or an item with no children is
	 * encountered. In a system that supports only simple versioning the connected
	 * non-versionable subtree will be equivalent to the whole subtree, since
	 * simple-versionable nodes cannot have simple-versionable descendants.
	 * Read-only status means that an item cannot be altered by the client using
	 * standard API methods (addNode, setProperty, etc.). The only exceptions to
	 * this rule are the restore(java.lang.String, boolean) (all signatures),
	 * restoreByLabel(java.lang.String, boolean),
	 * Workspace.restore(javax.jcr.version.Version[], boolean),
	 * merge(java.lang.String, boolean) and update(java.lang.String) operations;
	 * these do not respect read-only status due to check-in. Note that remove of
	 * a read-only node is possible, as long as its parent is not read-only (since
	 * removal is an alteration of the parent node).
	 *
	 * If this node is already checked-in, this method has no effect but returns
	 * the current base version of this node.
	 *
	 * If checkin succeeds, the change to the jcr:isCheckedOut property is
	 * automatically persisted (there is no need to do an additional save).
	 *
	 * @return F3_PHPCR_Version_VersionInterface the created version.
	 * @throws F3_PHPCR_Verson_VersionException if jcr:predecessors does not contain at least one value or if a child item of this node has an OnParentVersion status of ABORT. This includes the case where an unresolved merge failure exists on this node, as indicated by the presence of a jcr:mergeFailed property.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException If this node is not versionable.
	 * @throws F3_PHPCR_InvalidItemStateException If unsaved changes exist on this node.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the operation.
	 * @throws F3_PHPCR_RepositoryException If another error occurs.
	 */
	public function checkin() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667718);
	}

	/**
	 * Sets this versionable node to checked-out status by setting its jcr:isCheckedOut
	 * property to true. Under full versioning it also sets the jcr:predecessors
	 * property to be a reference to the current base version (the same value as
	 * held in jcr:baseVersion).
	 * This method puts the node into the checked-out state, making it and its
	 * connected non-versionable subtree no longer read-only (see checkin() for
	 * an explanation of the term "connected non-versionable subtree". Under
	 * simple versioning this will simply be the whole subtree).
	 *
	 * If successful, these changes are persisted immediately, there is no need to call save.
	 *
	 * If this node is already checked-out, this method has no effect.
	 *
	 * @return void
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException If this node is not versionable.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the checkout.
	 * @throws F3_PHPCR_Version_ActivityViolationException If the checkout conflicts with the activity present on the current session.
	 * @throws F3_PHPCR_RepositoryException If another error occurs.
	 */
	public function checkout() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667719);
	}

	/**
	 * Performs a checkin() followed by a checkout().
	 * If this node is already checked-in, this method is equivalent to checkout().
	 *
	 * @return F3_PHPCR_Version_VersionInterface the created version.
	 * @throws F3_PHPCR_Version_VersionException if a child item of this node has an OnParentVersion of ABORT. This includes the case where an unresolved merge failure exists on this node, as indicated by the presence of the jcr:mergeFailed.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not versionable.
	 * @throws F3_PHPCR_InvalidItemStateException if there are unsaved changes pending on this node.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the operation.
	 * @throws F3_PHPCR_Version_ActivityViolationException If the checkout conflicts with the activity present on the current session.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function checkpoint() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667720);
	}

	/**
	 * Completes the merge process with respect to this node and the specified version.
	 * Support for this method is only required under full versioning.
	 *
	 * When the merge(java.lang.String, boolean) method is called on a node, every
	 * versionable node in that subtree is compared with its corresponding node
	 * in the indicated other workspace and a "merge test result" is determined
	 * indicating one of the following:
	 *
	 * * This node will be updated to the state of its correspondee (if the base
	 *   version of the correspondee is more recent in terms of version history)
	 * * This node will be left alone (if this node's base version is more recent
	 *   in terms of version history).
	 * * This node will be marked as having failed the merge test (if this node's
	 *   base version is on a different branch of the version history from the base
	 *   version of its corresponding node in the other workspace, thus preventing
	 *   an automatic determination of which is more recent).
	 *   (See merge(java.lang.String, boolean) for more details)
	 *
	 * In the last case the merge of the non-versionable subtree (the "content")
	 * of this node must be done by the application (for example, by providing a
	 * merge tool for the user).
	 *
	 * Additionally, once the content of the nodes has been merged, their version
	 * graph branches must also be merged. The JCR versioning system provides for
	 * this by keeping a record, for each versionable node that fails the merge
	 * test, of the base version of the corresponding node that caused the merge
	 * failure. This record is kept in the jcr:mergeFailed property of this node.
	 * After a merge, this property will contain one or more (if multiple merges
	 * have been performed) REFERENCEs that point to the "offending versions".
	 *
	 * To complete the merge process, the client calls doneMerge(Version v) passing
	 * the version object referred to be the jcr:mergeFailed property that the
	 * client wishes to connect to this node in the version graph. This has the
	 * effect of moving the reference to the indicated version from the jcr:mergeFailed
	 * property of this node to the jcr:predecessors.
	 *
	 * If the client chooses not to connect this node to a particular version
	 * referenced in the jcr:mergeFailed property, he calls cancelMerge(Version
	 * version). This has the effect of removing the reference to the specified
	 * version from jcr:mergeFailed without adding it to jcr:predecessors.
	 *
	 * Once the last reference in jcr:mergeFailed has been either moved to
	 * jcr:predecessors (with doneMerge) or just removed from jcr:mergeFailed
	 * (with cancelMerge) the jcr:mergeFailed property is automatically removed,
	 * thus enabling this node to be checked-in, creating a new version (note that
	 * before the jcr:mergeFailed is removed, its OnParentVersion setting of ABORT
	 * prevents checkin). This new version will have a predecessor connection to
	 * each version for which doneMerge was called, thus joining those branches
	 * of the version graph.
	 *
	 * If successful, these changes are persisted immediately, there is no need
	 * to call save.
	 *
	 * @param F3_PHPCR_Version_VersionInterface $version a version referred to by this node's jcr:mergeFailed property.
	 * @return void
	 * @throws F3_PHPCR_Version_VersionException if the version specified is not among those referenced in this node's jcr:mergeFailed or if this node is currently checked-in.
	 * @throws F3_PHPCR_InvalidItemStateException if there are unsaved changes pending on this node.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not versionable.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function doneMerge(F3_PHPCR_Version_VersionInterface $version) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667721);
	}

	/**
	 * Cancels the merge process with respect to this node and specified version.
	 * Support for this method is only required under full versioning.
	 *
	 * See doneMerge(javax.jcr.version.Version) for a full explanation. Also see
	 * merge(java.lang.String, boolean) for more details.
	 *
	 * If successful, these changes are persisted immediately, there is no need
	 * to call save.
	 *
	 * @param F3_PHPCR_Version_VersionInterface $version a version referred to by this node's jcr:mergeFailed property.
	 * @return void
	 * @throws F3_PHPCR_Version_VersionException if the version specified is not among those referenced in this node's jcr:mergeFailed or if this node is currently checked-in.
	 * @throws F3_PHPCR_InvalidItemStateException  if there are unsaved changes pending on this node.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not versionable.
	 * @throws F3_PHPCR_RepositoryException  if another error occurs.
	 */
	public function cancelMerge(F3_PHPCR_Version_VersionInterface $version) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667722);
	}

	/**
	 * If this node does have a corresponding node in the workspace srcWorkspace,
	 * then this replaces this node and its subtree with a clone of the
	 * corresponding node and its subtree.
	 * If this node does not have a corresponding node in the workspace srcWorkspace,
	 * then the update method has no effect.
	 *
	 * If the update succeeds the changes made are persisted immediately, there
	 * is no need to call save.
	 *
	 * Note that update does not respect the checked-in status of nodes. An update
	 * may change a node even if it is currently checked-in (This fact is only
	 * relevant in an implementation that supports versioning).
	 *
	 * @param string $srcWorkspace the name of the source workspace.
	 * @return void
	 * @throws F3_PHPCR_NoSuchWorkspaceException if srcWorkspace does not exist.
	 * @throws F3_PHPCR_InvalidItemStateException if this Session (not necessarily this Node) has pending unsaved changes.
	 * @throws F3_PHPCR_AccessDeniedException if the current session does not have sufficient rights to perform the operation.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the update.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function update($srcWorkspace) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667723);
	}

	/**
	 * This method can be thought of as a version-sensitive update. It recursively
	 * tests each versionable node in the subtree of this node against its
	 * corresponding node in srcWorkspace with respect to the relation between
	 * their respective base versions and either updates the node in question or
	 * not, depending on the outcome of the test.
	 *
	 * Support for this method is only required under full versioning.
	 *
	 * If isShallow is given the following applies:
	 * * If isShallow is true, it tests this versionable node against its
	 *   corresponding node in srcWorkspace with respect to the relation between
	 *   their respective base versions and either updates the node in question
	 *   or not, depending on the outcome of the test.
	 * * If isShallow is false, it recursively tests each versionable node in
	 *   the subtree as mentioned above.
	 * * If isShallow is true and this node is not versionable, then this method
	 *   returns and no changes are made.
	 *
	 * If successful, the changes are persisted immediately, there is no need to
	 * call save.
	 *
	 * This method returns a NodeIterator over all versionable nodes in the subtree
	 * that received a merge result of fail. If bestEffort is false, this iterator
	 * will be empty (since if merge returns successfully, instead of throwing an
	 * exception, it will be because no failures were encountered). If bestEffort
	 * is true, this iterator will contain all nodes that received a fail during
	 * the course of this merge operation.
	 *
	 * A MergeException is thrown if bestEffort is false and a versionable node is
	 * encountered whose corresponding node's base version is on a divergent branch
	 * from this node's base version.
	 *
	 * @param string $srcWorkspace the name of the source workspace.
	 * @param boolean $bestEffort a boolean
	 * @param boolean $isShallow a boolean
	 * @return F3_PHPCR_NodeIteratorInterface iterator over all nodes that received a merge result of "fail" in the course of this operation.
	 * @throws F3_PHPCR_MergeException if bestEffort is false and a failed merge result is encountered.
	 * @throws F3_PHPCR_InvalidItemStateException  if this session (not necessarily this node) has pending unsaved changes.
	 * @throws F3_PHPCR_NoSuchWorkspaceException if srcWorkspace does not exist.
	 * @throws F3_PHPCR_AccessDeniedException  if the current session does not have sufficient rights to perform the operation.
	 * @throws F3_PHPCR_Lock_LockException  if a lock prevents the merge.
	 * @throws F3_PHPCR_RepositoryException  if another error occurs.
	 */
	public function merge($srcWorkspace, $bestEffort, $isShallow = NULL) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667724);
	}

	/**
	 * Calling createConfiguration on a node N creates, in the configuration
	 * storage, a new nt:configuration node whose root is N. A reference to N is
	 * recorded in the jcr:root property of the new configuration, and a reference
	 * to the new configuration is recorded in the jcr:configuration property of N.
	 * If the specified baseline is null, a new version history is created to store
	 * baselines of the new configuration, and the jcr:baseVersion of the new
	 * configuration references the root of the new version history. If the specified
	 * baseline is not null, the jcr:baseVersion of the new configuration references
	 * the specified baseline.
	 *
	 * The changes are persisted immediately, a save is not required.
	 *
	 * @param F3_PHPCR_Version_VersionInterface $baseline a Version
	 * @return F3_PHPCR_NodeInterface a new nt:configuration node
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if N is not versionable.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function createConfiguration(F3_PHPCR_Version_VersionInterface $baseline) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667725);
	}

	/**
	 * Returns the absolute path of the node in the specified workspace that
	 * corresponds to this node.
	 * If no corresponding node exists then an ItemNotFoundException is thrown.
	 *
	 * @param string $workspaceName the name of the workspace.
	 * @return string the absolute path to the corresponding node.
	 * @throws F3_PHPCR_ItemNotFoundException if no corresponding node is found.
	 * @throws F3_PHPCR_NoSuchWorkspaceException if the workspace is unknown.
	 * @throws F3_PHPCR_AccessDeniedException if the current session has insufficient rights to perform this operation.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function getCorrespondingNodePath($workspaceName) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667726);
	}

	/**
	 * Returns an iterator over all nodes that are in the shared set of this node.
	 * If this node is not shared then the returned iterator contains only this node.
	 *
	 * @return F3_PHPCR_NodeIteratorInterface a NodeIterator
	 * @throws F3_PHPCR_RepositoryException if an error occurs.
	 */
	public function getSharedSet() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667727);
	}

	/**
	 * A special kind of remove() that removes this node and every other node in
	 * the shared set of this node.
	 * This removal must be done atomically, i.e., if one of the nodes cannot be
	 * removed, the function throws the exception remove() would have thrown in
	 * that case, and none of the nodes are removed.
	 *
	 * If this node is not shared this method removes only this node.
	 *
	 * @return void
	 * @throws F3_PHPCR_Version_VersionException
	 * @throws F3_PHPCR_Lock_LockException
	 * @throws F3_PHPCR_ConstraintViolationException
	 * @throws F3_PHPCR_RepositoryException
	 */
	public function removeSharedSet() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667728);
	}

	/**
	 * A special kind of remove() that removes this node, but does not remove any
	 * other node in the shared set of this node.
	 * All of the exceptions defined for remove() apply to this function. In
	 * addition, a RepositoryException is thrown if this node cannot be removed
	 * without removing another node in the shared set of this node.
	 *
	 * If this node is not shared this method removes only this node.
	 *
	 * @return void
	 * @throws F3_PHPCR_Version_VersionException
	 * @throws F3_PHPCR_Lock_LockException
	 * @throws F3_PHPCR_ConstraintViolationException
	 * @throws F3_PHPCR_RepositoryException
	 */
	public function removeShare() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667729);
	}

	/**
	 * Places a hold on this node and its properties (if isDeep is false) or
	 * this node and its subtree (if isDeep is true).
	 *
	 * The supplied holdID is added to the jcr:hold multi-value property and the
	 * corresponding jcr:isDeep value is set accordingly. The corresponding
	 * jcr:isDeep value is the one with the same index as the holdID value.
	 *
	 * The format and interpretation of the holdID is not specified.
	 * It is expected to be an identifier associated with the application placing
	 * the hold.
	 *
	 * @param string $holdID a string
	 * @param boolean $isDeep a boolean
	 * @return void
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not of type mix:managedRetention.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function setHold($holdID, $isDeep) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213802139);
	}

	/**
	 * Removes the specified holdID and the corresponding boolean flag from the
	 * jcr:hold and jcr:isDeep properties of this node, respectively.
	 *
	 * If this is the last holdID in the property then the hold on this node is
	 * lifted.
	 *
	 * @param string $holdID a string
	 * @return void
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not of type mix:managedRetention.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function removeHold($holdID) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213802140);
	}

	/**
	 * Sets the retention policy of this node to that defined in the specified
	 * policy node. Interpretation and enforcement of this policy is an
	 * implementation issue.
	 *
	 * The jcr:retentionPolicy property of this node is set to
	 * refer to the policy node.
	 *
	 * @param F3_PHPCR_NodeInterface $policy a policy node
	 * @return void
	 * @throws F3_PHPCR_NodeType_ConstraintViolationException if the specified node is not a valid retention policy node.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not of type mix:managedRetention.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function setRetentionPolicy(F3_PHPCR_NodeInterface $policy) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213802141);
	}

	/**
	 * Causes the current retention policy on this node to no longer apply.
	 *
	 * Removes the jcr:retentionPolicy property from this node.
	 *
	 * @return void
	 * @throws F3_PHPCR_NodeType_ConstraintViolationException if this node does not have a retention policy currently assigned.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not of type mix:managedRetention.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function removeRetentionPolicy() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1213802142);
	}

	/**
	 * Returns true if this node is either
	 * versionable (full or simple) and currently checked-out,
	 * non-versionable and its nearest versionable ancestor is checked-out or
	 * non-versionable and it has no versionable ancestor.
	 * Returns false if this node is either
	 * versionable (full or simple) and currently checked-in or
	 * non-versionable and its nearest versionable ancestor is checked-in.
	 *
	 * @return boolean a boolean
	 * @throws F3_PHPCR_RepositoryException If another error occurs.
	 */
	public function isCheckedOut() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667730);
	}

	/**
	 * Restores this node to the state defined by the version with the specified
	 * versionName.
	 *
	 * If relPath is given:
	 * * The specified version is restored to to relPath, relative to this node.
	 *   A node need not exist at relPath, though the parent of relPath must exist,
	 *   otherwise a PathNotFoundException is thrown.
	 * * If a node does exist at relPath then it must correspond to the version
	 *   being restored (the version must be a version of that node) and must not
	 *   be a root version (jcr:rootVersion), otherwise a VersionException is thrown.
	 * * If no node exists at relPath then a VersionException is thrown if the
	 *   parent node of relPath is versionable and checked-in or is non-versionable
	 *   but its nearest versionable ancestor is checked-in.
	 * * If there is a node at relPath then the checked-in status of that node
	 *   itself and the checked-in status of its parent are irrelevant. The restore
	 *   will work even if one or both are checked-in.
	 *
	 * If the would-be parent of the location relPath is actually a property, or
	 * if a node type restriction would be violated, then a ConstraintViolationException
	 * is thrown.
	 *
	 * An identifier collision occurs when a node exists outside the subtree rooted
	 * at relPath with the same identifier as a node that would be introduced by the
	 * restore operation into the subtree at relPath (Note that in cases where there
	 * is no node at relPath, this amounts to saying that an identifier collision
	 * occurs if there exists a node anywhere in this workspace with the same
	 * identifier as a node that would be introduced by the restore). The result
	 * in such a case is governed by the removeExisting flag. If removeExisting is
	 * true, then the incoming node takes precedence, and the existing node (and
	 * its subtree) is removed (if possible; otherwise a RepositoryException is
	 * thrown). If removeExisting is false, then a ItemExistsException is thrown
	 * and no changes are made. Note that this applies not only to cases where the
	 * restored node itself conflicts with an existing node but also to cases where
	 * a conflict occurs with any node that would be introduced into the workspace
	 * by the restore operation. In particular, conflicts involving subnodes of the
	 * restored node that have OnParentVersion settings of COPY or VERSION are also
	 * governed by the removeExisting flag.
	 *
	 * If the restore succeeds, the changes made to this node are persisted
	 * immediately, there is no need to call save.
	 *
	 * @param string|F3_PHPCR_Version_VersionInterface $version a version object or a version name
	 * @param boolean $removeExisting covers what happens on identifier collision.
	 * @param string $relPath the path to which the version is to be restored
	 * @return void
	 * @throws F3_PHPCR_PathNotFoundException if the parent of relPath does not exist.
	 * @throws F3_PHPCR_ItemExistsException if removeExisting is false and an identifier collision occurs
	 * @throws F3_PHPCR_ConstraintViolationException If the would-be parent of the location relPath is actually a property, or if a node type restriction would be violated
	 * @throws F3_PHPCR_Version_VersionException if the parent node of relPath is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in or if a node exists at relPath that is not the node corresponding to the specified version or if an attempt is made to restore the root version (jcr:rootVersion).
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if versioning is not supported.
	 * @throws F3_PHPCR_Lock_LockException  if a lock prevents the restore.
	 * @throws F3_PHPCR_InvalidItemStateException  if this Session (not necessarily this Node) has pending unsaved changes.
	 * @throws F3_PHPCR_RepositoryException  if another error occurs
	 */
	public function restore($version, $removeExisting, $relPath = NULL) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667731);
	}

	/**
	 * Restores the version of this node with the specified version label. If this
	 * node is not versionable, an UnsupportedRepositoryOperationException is
	 * thrown. If successful, the change is persisted immediately and there is
	 * no need to call save.
	 *
	 * This method will work regardless of whether this node is checked-in or not.
	 *
	 * An identifier collision occurs when a node exists outside the subtree
	 * rooted at this node with the same identifier as a node that would be
	 * introduced by the restoreByLabel operation into the subtree at this node.
	 * The result in such a case is governed by the removeExisting flag. If
	 * removeExisting is true, then the incoming node takes precedence, and the
	 * existing node (and its subtree) is removed (if possible; otherwise a
	 * RepositoryException is thrown). If removeExisting is false, then a
	 * ItemExistsException is thrown and no changes are made. Note that this
	 * applies not only to cases where the restored node itself conflicts with
	 * an existing node but also to cases where a conflict occurs with any node
	 * that would be introduced into the workspace by the restore operation. In
	 * particular, conflicts involving subnodes of the restored node that have
	 * OnParentVersion settings of COPY or VERSION are also governed by the
	 * removeExisting flag.
	 *
	 * @param string $versionLabel a String
	 * @param boolean $removeExisting a boolean flag that governs what happens in case of an identifier collision.
	 * @return void
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not versionable.
	 * @throws F3_PHPCR_Version_VersionException if the specified versionLabel does not exist in this node's version history.
	 * @throws F3_PHPCR_ItemExistsException if removeExisting is false and an identifier collision occurs.
	 * @throws F3_PHPCR_Lock_LockException if a lock prevents the restore.
	 * @throws F3_PHPCR_InvalidItemStateException if this Session (not necessarily this Node) has pending unsaved changes.
	 * @throws F3_PHPCR_RepositoryException If another error occurs.
	 */
	public function restoreByLabel($versionLabel, $removeExisting) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667732);
	}

	/**
	 * Returns the VersionHistory object of this node. This object provides access
	 * to the nt:versionHistory node holding this node's versions.
	 *
	 * @return F3_PHPCR_Version_VersionHistoryInterface a VersionHistory object
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not versionable.
	 * @throws F3_PHPCR_RepositoryException If another error occurs.
	 */
	public function getVersionHistory() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667733);
	}

	/**
	 * Returns the current base version of this versionable node.
	 *
	 * @return F3_PHPCR_Version_VersionInterface a Version object.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException if this node is not versionable.
	 * @throws F3_PHPCR_RepositoryException if another error occurs.
	 */
	public function getBaseVersion() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667734);
	}

	/**
	 * Causes the lifecycle state of this node to undergo the specified transition.
	 * This method may change the value of the jcr:currentLifecycleState property,
	 * in most cases it is expected that the implementation will change the value
	 * to that of the passed transition parameter, though this is an
	 * implementation-specific issue. If the jcr:currentLifecycleState property
	 * is changed the change is persisted immediately, there is no need to call
	 * save.
	 *
	 * @param string $transition a state transition
	 * @return void
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException  if this implementation does not support lifecycle actions or if this node does not have the mix:lifecycle mixin.
	 * @throws F3_PHPCR_InvalidLifecycleTransitionException if the lifecycle transition is not successful.
	 * @throws F3_PHPCR_RepositoryException  if another error occurs.
	 */
	public function followLifecycleTransition($transition) {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667740);
	}

	/**
	 * Returns the list of valid state transitions for this node.
	 *
	 * @return array a string array.
	 * @throws F3_PHPCR_UnsupportedRepositoryOperationException  if this implementation does not support lifecycle actions or if this node does not have the mix:lifecycle mixin.
	 * @throws F3_PHPCR_RepositoryException  if another error occurs.
	 */
	public function getAllowedLifecycleTransitions() {
		throw new F3_PHPCR_UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1212667741);
	}

}
?>