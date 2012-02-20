<?php
namespace TYPO3\TYPO3CR\Domain\Repository;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \Doctrine\ORM\Query;

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The repository for nodes
 *
 * @FLOW3\Scope("singleton")
 */
class NodeRepository extends \TYPO3\FLOW3\Persistence\Repository {

	/**
	 * Constants for setNewIndex()
	 */
	const POSITION_BEFORE = 1;
	const POSITION_AFTER = 2;
	const POSITION_LAST = 3;

	/**
	 * Maximum possible index
	 */
	const INDEX_MAXIMUM = 2147483647;

	/**
	 * @var \SplObjectStorage
	 */
	protected $addedNodes;

	/**
	 * @var \SplObjectStorage
	 */
	protected $removedNodes;

	/**
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
	 * interface ...
	 *
	 * @FLOW3\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array(
		'index' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->addedNodes = new \SplObjectStorage();
		$this->removedNodes = new \SplObjectStorage();
		parent::__construct();
	}

	/**
	 * Adds a Node to this repository.
	 *
	 * This repository keeps track of added and removed nodes (additionally to the
	 * other Unit of Work) in order to find in-memory nodes.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		$this->addedNodes->attach($object);
		$this->removedNodes->detach($object);
		parent::add($object);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * This repository keeps track of added and removed nodes (additionally to the
	 * other Unit of Work) in order to find in-memory nodes.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if ($this->addedNodes->contains($object)) {
			$this->addedNodes->detach($object);
		}
		$this->removedNodes->attach($object);
		parent::remove($object);
	}

	/**
	 * Finds a node by its path and workspace.
	 *
	 * If the node does not exist in the specified workspace, this function will
	 * try to find one with the given path in one of the base workspaces (if any).
	 *
	 * Examples for valid paths:
	 *
	 * /          the root node
	 * /foo       node "foo" on the first level
	 * /foo/bar   node "bar" on the second level
	 * /foo/      first node on second level, below "foo"
	 *
	 * @param string $path Absolute path of the node
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface The matching node if found, otherwise NULL
	 */
	public function findOneByPath($path, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if (strlen($path) === 0 || ($path !== '/' && ($path[0] !== '/' || substr($path, -1, 1) === '/'))) {
			throw new \InvalidArgumentException('"' . $path . '" is not a valid path: must start but not end with a slash.', 1284985489);
		}

		if ($path === '/') {
			return $workspace->getRootNode();
		}

		while($workspace !== NULL) {

			foreach ($this->addedNodes as $node) {
				if ($node->getPath() === $path && $node->getWorkspace() === $workspace) {
					return $node;
				}
			}

			foreach ($this->removedNodes as $node) {
				if ($node->getPath() === $path && $node->getWorkspace() === $workspace) {
					return NULL;
				}
			}

			$query = $this->entityManager->createQuery('SELECT n FROM TYPO3\TYPO3CR\Domain\Model\Node n WHERE n.path = :path AND n.workspace = :workspace');
			$query->setParameter('path', $path);
			$query->setParameter('workspace', $workspace);

			$node = $query->getOneOrNullResult();
			if ($node !== NULL) {
				return $node;
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		return NULL;
	}

	/**
	 * Assigns an index to the given node which reflects the specified position.
	 * If the position is "before" or "after", an index will be chosen which makes
	 * the given node the previous or next node of the given reference node.
	 * If the position "last" is specified, an index higher than any existing index
	 * will be chosen.
	 *
	 * If no free index is available between two nodes (for "before" and "after"),
	 * the whole index of the current node level will be renumbered.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node The node to set the new index for
	 * @param integer $position The position the new index should reflect, must be one of the POSITION_* constants
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode The reference node. Mandatory for POSITION_BEFORE and POSITION_AFTER
	 * @return void
	 */
	public function setNewIndex(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $position, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode = NULL) {
		$parentPath = $node->getParentPath();

		switch ($position) {
			case self::POSITION_BEFORE:
				if ($referenceNode === NULL) {
					throw new \InvalidArgumentException('The reference node must be specified for POSITION_BEFORE.', 1317198857);
				}
				$referenceIndex = $referenceNode->getIndex();
				$nextLowerIndex = $this->findNextLowerIndex($parentPath, $referenceIndex);
				if ($nextLowerIndex === NULL) {
					$newIndex = (integer)round($referenceIndex / 2);
				} elseif ($nextLowerIndex < ($referenceIndex - 1)) {
					$newIndex = (integer)round($nextLowerIndex + (($referenceIndex - $nextLowerIndex) / 2));
				} else {
					$this->renumberIndexesInLevel($parentPath);
					$referenceIndex = $referenceNode->getIndex();
					$nextLowerIndex = $this->findNextLowerIndex($parentPath, $referenceIndex);
					if ($nextLowerIndex === NULL) {
						$newIndex = (integer)round($referenceIndex / 2);
					} else {
						$newIndex = (integer)round($nextLowerIndex + (($referenceIndex - $nextLowerIndex) / 2));
					}
				}
			break;
			case self::POSITION_AFTER:
				if ($referenceNode === NULL) {
					throw new \InvalidArgumentException('The reference node must be specified for POSITION_AFTER.', 1317198858);
				}
				$referenceIndex = $referenceNode->getIndex();
				$nextHigherIndex = $this->findNextHigherIndex($parentPath, $referenceIndex);
				if ($nextHigherIndex === NULL) {
					$newIndex = $referenceIndex + 100;
				} elseif ($nextHigherIndex > ($referenceIndex + 1)) {
					$newIndex = (integer)round($referenceIndex + (($nextHigherIndex - $referenceIndex) / 2));
				} else {
					$this->renumberIndexesInLevel($parentPath);
					$referenceIndex = $referenceNode->getIndex();
					$nextHigherIndex = $this->findNextHigherIndex($parentPath, $referenceIndex);
					if ($nextHigherIndex === NULL) {
						$newIndex = $referenceIndex + 100;
					} else {
						$newIndex = (integer)round($referenceIndex + (($nextHigherIndex - $referenceIndex) / 2));
					}
				}
			break;
			case self::POSITION_LAST:
				$highestIndex = $this->findHighestIndexInLevel($parentPath);
				$newIndex = $highestIndex + 100;
			break;
		}

		$node->setIndex($newIndex);
	}

	/**
	 * Finds nodes by its parent and (optionally) by its content type.
	 *
	 * Note: Filters out removed nodes.
	 *
	 * The primary sort key is the *index*, the secondary sort key (if indices are equal, which
	 * only occurs in very rare cases) is the *identifier*.
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentTypeFilter Filter the content type of the nodes, allows complex expressions (e.g. "TYPO3.TYPO3:Page", "!TYPO3.TYPO3:Page,TYPO3.TYPO3:Text" or NULL)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return array<\TYPO3\TYPO3\Domain\Model\Node> The nodes found on the given path
	 * @todo Improve implementation by using DQL
	 */
	public function findByParentAndContentType($parentPath, $contentTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$foundNodes = array();

		while ($workspace !== NULL) {
			$query = $this->createQueryForFindByParentAndContentType($parentPath, $contentTypeFilter, $workspace);
			$nodesFoundInThisWorkspace = $query->execute()->toArray();
			foreach ($nodesFoundInThisWorkspace as $node) {
				if (!isset($foundNodes[$node->getIdentifier()])) {
					$foundNodes[$node->getIdentifier()] = $node;
				}
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		if ($parentPath === '/') {
			foreach ($this->addedNodes as $addedNode) {
				if ($addedNode->getDepth() === 1) {
					$foundNodes[$addedNode->getIdentifier()] = $addedNode;
				}
			}
		} else {
			$childNodeDepth = substr_count($parentPath, '/') + 1;
			foreach ($this->addedNodes as $addedNode) {
				if ($addedNode->getDepth() === $childNodeDepth && substr($addedNode->getPath(), 0, strlen($parentPath) + 1) === ($parentPath . '/')) {
					$foundNodes[$addedNode->getIdentifier()] = $addedNode;
				}
			}
		}

		return $this->sortNodesByIndex($foundNodes);
	}

	/**
	 * Renumbers the indexes of all nodes directly below the node specified by the
	 * given path.
	 *
	 * Note that renumbering must happen in-memory and can't be optimized by a clever
	 * query executed directly by the database because sorting indexes of new or
	 * modified nodes need to be considered.
	 *
	 * @param string $parentPath Path to the parent node
	 * @return void
	 */
	protected function renumberIndexesInLevel($parentPath) {
		$this->systemLogger->log(sprintf('Renumbering nodes in level below %s.', $parentPath), LOG_INFO);

		$query = $this->entityManager->createQuery('SELECT n FROM TYPO3\TYPO3CR\Domain\Model\Node n WHERE n.parentPath = :parentPath ORDER BY n.index ASC');
		$query->setParameter('parentPath', $parentPath);

		$nodesOnLevel = array();
		foreach ($query->getResult() as $node) {
			$nodesOnLevel[$node->getIndex()] = $node;
		}

		foreach ($this->addedNodes as $node) {
			if ($node->getParentPath() === $parentPath) {
				$index = $node->getIndex();
				if (isset($nodesOnLevel[$index])) {
					throw new \TYPO3\TYPO3CR\Exception\NodeException(sprintf('Index conflict for nodes %s and %s: both have index %s', $nodesOnLevel[$index]->getPath(), $node->getPath(), $index), 1317140401);
				}
				$nodesOnLevel[$index] = $node;
			}
		}

		$newIndex = 100;
		foreach ($nodesOnLevel as $node) {
			if ($newIndex > self::INDEX_MAXIMUM) {
				throw new \TYPO3\TYPO3CR\Exception\NodeException(sprintf('Reached maximum node index of %s while setting index of node %s.', $newIndex, $node->getPath()), 1317140402);
			}
			$node->setIndex($newIndex);
			$newIndex += 100;
		}
	}

	/**
	 * Finds the currently highest index in the level below the given parent path
	 * across all workspaces.
	 *
	 * @param string $parentPath Path of the parent node specifying the level in the node tree
	 * @return integer The currently highest index
	 */
	protected function findHighestIndexInLevel($parentPath) {
		$query = $this->entityManager->createQuery('SELECT MAX(n.index) FROM TYPO3\TYPO3CR\Domain\Model\Node n WHERE n.parentPath = :parentPath');
		$query->setParameter('parentPath', $parentPath);
		$highestIndex = $query->getSingleScalarResult() ?: 0;

		foreach ($this->addedNodes as $node) {
			if ($node->getParentPath() === $parentPath && $node->getIndex() > $highestIndex) {
				$highestIndex = $node->getIndex();
			}
		}
		return $highestIndex;
	}

	/**
	 * Returns the next-lower-index seen from the given reference index in the
	 * level below the specified parent path. If no node with a lower than the
	 * given index exists at that level, the reference index is returned.
	 *
	 * The result is determined workspace-agnostic.
	 *
	 * @param string $parentPath Path of the parent node specifying the level in the node tree
	 * @param integer $referenceIndex Index of a known node
	 * @return integer The currently next lower index
	 */
	protected function findNextLowerIndex($parentPath, $referenceIndex) {
		$query = $this->entityManager->createQuery('SELECT MAX(n.index) FROM TYPO3\TYPO3CR\Domain\Model\Node n WHERE n.parentPath = :parentPath AND n.index < :referenceIndex');
		$query->setParameter('parentPath', $parentPath);
		$query->setParameter('referenceIndex', $referenceIndex);
		$nextLowerIndex = $query->getSingleScalarResult() ?: 0;

		foreach ($this->addedNodes as $node) {
			$otherIndex = $node->getIndex();
			if ($node->getParentPath() === $parentPath && $otherIndex > $nextLowerIndex && $otherIndex < $referenceIndex) {
				$nextLowerIndex = $otherIndex;
			}
		}

		return $nextLowerIndex;
	}

	/**
	 * Returns the next-higher-index seen from the given reference index in the
	 * level below the specified parent path. If no node with a higher than the
	 * given index exists at that level, the reference index is returned.
	 *
	 * The result is determined workspace-agnostic.
	 *
	 * @param string $parentPath Path of the parent node specifying the level in the node tree
	 * @param integer $referenceIndex Index of a known node
	 * @return integer The currently next higher index
	 */
	protected function findNextHigherIndex($parentPath, $referenceIndex) {
		$query = $this->entityManager->createQuery('SELECT MIN(n.index) FROM TYPO3\TYPO3CR\Domain\Model\Node n WHERE n.parentPath = :parentPath AND n.index > :referenceIndex');
		$query->setMaxResults(1);
		$query->setParameter('parentPath', $parentPath);
		$query->setParameter('referenceIndex', $referenceIndex);
		$nextHigherIndex = $query->getSingleScalarResult() ?: NULL;

		foreach ($this->addedNodes as $node) {
			$otherIndex = $node->getIndex();
			if ($node->getParentPath() === $parentPath && ($nextHigherIndex === NULL || $otherIndex < $nextHigherIndex) && $otherIndex > $referenceIndex) {
				$nextHigherIndex = $otherIndex;
			}
		}

		return $nextHigherIndex;
	}

	/**
	 * Counts the number of nodes within the specified workspace
	 *
	 * Note: Also counts removed nodes
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return integer The number of nodes found
	 */
	public function countByWorkspace(\TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		$query = $this->createQuery();
		$nodesInDatabase = $query->matching($query->equals('workspace', $workspace))->execute()->count();

		$nodesInMemory = 0;
		foreach ($this->addedNodes as $node) {
			if ($node->getWorkspace() === $workspace) {
				$nodesInMemory ++;
			}
		}

		return $nodesInDatabase + $nodesInMemory;
	}

	/**
	 * Sorts the given nodes by their index
	 *
	 * @param array $nodes Nodes
	 * @return array Nodes sorted by index
	 */
	protected function sortNodesByIndex(array $nodes)	{
		usort($nodes, function($element1, $element2)
			{
				if ($element1->getIndex() < $element2->getIndex()) {
					return -1;
				} elseif ($element1->getIndex() > $element2->getIndex()) {
					return 1;
				} else {
					return strcmp($element1->getIdentifier(), $element2->getIdentifier());
				}
			});
		return $nodes;
	}

	/**
	 * Finds a single node by its parent and (optionally) by its content type
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentTypeFilter Filter the content type of the nodes, allows complex expressions (e.g. "TYPO3.TYPO3:Page", "!TYPO3.TYPO3:Page,TYPO3.TYPO3:Text" or NULL)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \TYPO3\TYPO3\Domain\Model\Node The node found or NULL
	 * @todo Check for workspace compliance
	 */
	public function findFirstByParentAndContentType($parentPath, $contentTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		while ($workspace !== NULL) {
			$query = $this->createQueryForFindByParentAndContentType($parentPath, $contentTypeFilter, $workspace);
			$firstNodeFoundInThisWorkspace = $query->execute()->getFirst();
			if ($firstNodeFoundInThisWorkspace !== NULL) {
				return $firstNodeFoundInThisWorkspace;
			}
			$workspace = $workspace->getBaseWorkspace();
		}
		return NULL;
	}

	/**
	 * Finds all nodes of the specified workspace lying on the path specified by
	 * (and including) the given starting point and end point.
	 *
	 * If some node does not exist in the specified workspace, this function will
	 * try to find a corresponding node in one of the base workspaces (if any).
	 *
	 * @param string $pathStartingPoint Absolute path specifying the starting point
	 * @param string $pathEndPoint Absolute path specifying the end point
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return array<\TYPO3\TYPO3\Domain\Model\Node> The nodes found on the given path
	 */
	public function findOnPath($pathStartingPoint, $pathEndPoint, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if ($pathStartingPoint !== substr($pathEndPoint, 0, strlen($pathStartingPoint))) {
			throw new \InvalidArgumentException('Invalid paths: path of starting point must first part of end point path.', 1284391181);
		}

		$foundNodes = array();
		$pathSegments = explode('/', substr($pathEndPoint, strlen($pathStartingPoint)));

		while($workspace !== NULL && count($foundNodes) < count($pathSegments)) {
			$query = $this->createQuery();
			$pathConstraints = array();
			$constraintPath = $pathStartingPoint;

			foreach ($pathSegments as $pathSegment) {
				$constraintPath .= $pathSegment;
				$pathConstraints[] = $query->equals('path', $constraintPath);
				$constraintPath .= '/';
			}

			$query->matching(
				$query->logicalAnd(
					$query->logicalOr($pathConstraints),
					$query->equals('workspace', $workspace)
				)
			);

			$nodesInThisWorkspace = $query->execute()->toArray();
			foreach ($nodesInThisWorkspace as $node) {
				if (!isset($foundNodes[$node->getDepth()])) {
					$foundNodes[$node->getDepth()] = $node;
				}
			}
			$workspace = $workspace->getBaseWorkspace();
		}

		ksort($foundNodes);
		return (count($foundNodes) === count($pathSegments)) ? array_values($foundNodes) : array();
	}

	/**
	 * Flushes the addedNodes and removedNodes registry.
	 *
	 * This method is (and should only be) used as a slot to the allObjectsPersisted
	 * signal.
	 *
	 * @return void
	 */
	public function flushNodeRegistry() {
		$this->addedNodes = new \SplObjectStorage();
		$this->removedNodes = new \SplObjectStorage();
	}

	/**
	 * @param string $contentTypeFilter
	 * @return string
	 * @fixme implement
	 */
	protected function getDqlContentTypeFilterConstraints($contentTypeFilter) {
		if ($contentTypeFilter === NULL) {
			return '';
		}

		return '';

		$constraints = '';
		$includeContentTypeConstraints = array();
		$excludeContentTypeConstraints = array();

		$contentTypeFilterParts = explode(',', $contentTypeFilter);
		foreach ($contentTypeFilterParts as $contentTypeFilterPart) {
			if (strpos($contentTypeFilterPart, '!') === 0) {
				$excludeContentTypeConstraints[] = "NOT n.contentType = '" . substr($contentTypeFilterPart, 1) . "'";
			} else {
				$includeContentTypeConstraints[] = "n.contentType = '" . substr($contentTypeFilterPart, 1) . "'";
			}
		}
		if (count($excludeContentTypeConstraints) > 0) {
		}
		if (count($includeContentTypeConstraints) > 0) {
		}
	}

	/**
	 * Creates a query for findinnodes by its parent and (optionally) by its content type.
	 *
	 * Does not traverse base workspaces, returns ary query only matching nodes of
	 * the given workspace.
	 *
	 * @param string $parentPath Absolute path of the parent node
	 * @param string $contentTypeFilter Filter the content type of the nodes, allows complex expressions (e.g. "TYPO3.TYPO3:Page", "!TYPO3.TYPO3:Page,TYPO3.TYPO3:Text" or NULL)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace The containing workspace
	 * @return \TYPO3\FLOW3\Peristence\QueryInterface The query
	 */
	protected function createQueryForFindByParentAndContentType($parentPath, $contentTypeFilter, \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace) {
		if (strlen($parentPath) === 0 || ($parentPath !== '/' && ($parentPath[0] !== '/' || substr($parentPath, -1, 1) === '/'))) {
			throw new \InvalidArgumentException('"' . $parentPath . '" is not a valid path: must start but not end with a slash.', 1284985610);
		}

		$query = $this->createQuery();
		$constraints = array(
			$query->equals('workspace', $workspace),
			$query->equals('parentPath', $parentPath),
		);

		if ($parentPath !== '/') {
			$constraints[] = $query->like('path', $parentPath . '/%');
		}

		if ($contentTypeFilter !== NULL) {
			$includeContentTypeConstraints = array();
			$excludeContentTypeConstraints = array();
			$contentTypeFilterParts = explode(',', $contentTypeFilter);
			foreach ($contentTypeFilterParts as $contentTypeFilterPart) {
				if (strpos($contentTypeFilterPart, '!') === 0) {
					$excludeContentTypeConstraints[] = $query->logicalNot($query->equals('contentType', substr($contentTypeFilterPart, 1)));
				} else {
					$includeContentTypeConstraints[] = $query->equals('contentType', $contentTypeFilterPart);
				}
			}
			if (count($excludeContentTypeConstraints) > 0) {
				$constraints = array_merge($excludeContentTypeConstraints, $constraints);
			}
			if (count($includeContentTypeConstraints) > 0) {
				$constraints[] = $query->logicalOr($includeContentTypeConstraints);
			}
		}

		$query->matching($query->logicalAnd($constraints));
		return $query;
	}

	/**
	 * Iterates of the array of objects and removes all those which have recently been removed from the repository,
	 * but whose removal has not yet been persisted.
	 *
	 * Technically this is a check of the given array against $this->removedNodes.
	 *
	 * @param array &$objects An array of objects to filter, passed by reference.
	 * @return void
	 */
	protected function filterOutRemovedObjects(array &$objects) {
		foreach ($objects as $index => $object) {
			if ($this->removedNodes->contains($object)) {
				unset($objects[$index]);
			}
		}
	}
}
?>