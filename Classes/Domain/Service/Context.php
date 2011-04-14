<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Domain\Service;

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
 * Context
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Context implements \F3\TYPO3CR\Domain\Service\ContextInterface {

	/**
	 * @var \F3\TYPO3CR\Domain\Model\Workspace
	 */
	protected $workspace;

	/**
	 * @var string
	 */
	protected $workspaceName;

	/**
	 * @var \F3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $currentNode;

	/**
	 * @var \F3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructs this context.
	 *
	 * @param string $workspaceName
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($workspaceName) {
		$this->workspaceName = $workspaceName;
		$this->currentDateTime = new \DateTime();
	}

	/**
	 * @param \F3\TYPO3CR\Domain\Repository\NodeRepository $nodeRepository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectNodeRepository(\F3\TYPO3CR\Domain\Repository\NodeRepository $nodeRepository) {
		$this->nodeRepository = $nodeRepository;
	}

	/**
	 * @param \F3\TYPO3CR\Domain\Repository\WorkspaceRepository $workspaceRepository
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectWorkspaceRepository(\F3\TYPO3CR\Domain\Repository\WorkspaceRepository $workspaceRepository) {
		$this->workspaceRepository = $workspaceRepository;
	}

	/**
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns the current workspace.
	 *
	 * @param boolean $createWorkspaceIfNecessary If enabled, creates a workspace with the configured name if it doesn't exist already
	 * @return \F3\TYPO3CR\Domain\Model\Workspace The workspace or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getWorkspace($createWorkspaceIfNecessary = TRUE) {
		if ($this->workspace === NULL) {
			$this->workspace = $this->workspaceRepository->findOneByName($this->workspaceName);
			if (!$this->workspace) {
				if ($createWorkspaceIfNecessary === FALSE) {
					return NULL;
				}
				$liveWorkspace = $this->workspaceRepository->findOneByName('live');
				if (!$liveWorkspace) {
					$liveWorkspace = $this->objectManager->create('F3\TYPO3CR\Domain\Model\Workspace', 'live');
					$this->workspaceRepository->add($liveWorkspace);
				}
				$this->workspace = ($this->workspaceName === 'live') ? $liveWorkspace : $this->objectManager->create('F3\TYPO3CR\Domain\Model\Workspace', $this->workspaceName, $liveWorkspace);
			}
			$this->workspace->setContext($this);
		}
		return $this->workspace;
	}

	/**
	 * Sets the current node.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCurrentNode(\F3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->currentNode = $node;
	}

	/**
	 * Returns the current node
	 *
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentNode() {
		return $this->currentNode;
	}

	/**
	 * Returns the current date and time in form of a \DateTime
	 * object.
	 *
	 * If you use this method for getting the current date and time
	 * everywhere in your code, it will be possible to simulate a certain
	 * time in unit tests or in the actual application (for realizing previews etc).
	 *
	 * @return \DateTime The current date and time - or a simulated version of it
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCurrentDateTime() {
		return $this->currentDateTime;
	}

	/**
	 * Sets the simulated date and time. This time will then always be returned
	 * by getCurrentDateTime().
	 *
	 * @param \DateTime $currentDateTime A date and time to simulate.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCurrentDateTime(\DateTime $currentDateTime) {
		$this->currentDateTime = $currentDateTime;
	}

	/**
	 * Returns a node specified by the given absolute path.
	 *
	 * @param string $path Absolute path specifying the node
	 * @return \F3\TYPO3CR\Domain\Model\NodeInterface The specified node or NULL if no such node exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNode($path) {
		if (!is_string($path) || $path[0] !== '/') {
			throw new \InvalidArgumentException('Only absolute paths are allowed for Context::getNode()', 1284975105);
		}
		return ($path === '/') ? $this->getWorkspace()->getRootNode() : $this->getWorkspace()->getRootNode()->getNode(substr($path, 1));
	}

	/**
	 * Finds all nodes lying on the path specified by (and including) the given
	 * starting point and end point.
	 *
	 * @param mixed $startingPoint Either an absolute path or an actual node specifying the starting point, for example /sites/mysite.com/
	 * @param mixed $endPoint Either an absolute path or an actual node specifying the end point, for example /sites/mysite.com/homepage/subpage
	 * @return array<\F3\TYPO3CR\Domain\Model\NodeInterface> The nodes found between and including the given paths or an empty array of none were found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getNodesOnPath($startingPoint, $endPoint) {
		$startingPointPath = ($startingPoint instanceof \F3\TYPO3CR\Domain\Model\NodeInterface) ? $startingPoint->getPath() : $startingPoint;
		$endPointPath = ($endPoint instanceof \F3\TYPO3CR\Domain\Model\NodeInterface) ? $endPoint->getPath() : $endPoint;

		$nodes = $this->nodeRepository->findOnPath($startingPointPath, $endPointPath, $this->workspace);
		foreach ($nodes as $node) {
			$node->setContext($this);
		}
		return $nodes;
	}

	/**
	 * Returns this context as a "context path"
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __toString() {
		return $this->workspaceName . $this->currentNode->getPath();
	}
}
?>