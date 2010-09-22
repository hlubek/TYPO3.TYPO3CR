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
 * A Workspace
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @entity
 * @scope prototype
 */
class Workspace {

	/**
	 * @var string
	 * @identity
	 * @validate StringLength(minimum = 1, maximum = 200)
	 */
	protected $name;

	/**
	 * Workspace (if any) this workspace is based on.
	 * 
	 * Content from the base workspace will shine through in this workspace
	 * as long as they are not modified in this workspace.
	 * 
	 * @var \F3\TYPO3CR\Domain\Model\Workspace
	 */
	protected $baseWorkspace;

	/**
	 * Root node of this workspace
	 *
	 * @var \F3\TYPO3CR\Domain\Model\Node
	 */
	protected $rootNode;

	/**
	 * @var \F3\TYPO3CR\Domain\Service\Context
	 * @transient
	 */
	protected $context;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 * @transient
	 */
	protected $objectManager;

	/**
	 * Constructs a new workspace
	 *
	 * @param string $name Name of this workspace
	 * @param \F3\TYPO3CR\Domain\Model\Workspace $baseWorkspace A workspace this workspace is based on (if any)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name, \F3\TYPO3CR\Domain\Model\Workspace $baseWorkspace = NULL) {
		$this->name = $name;
		$this->baseWorkspace = $baseWorkspace;
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
	 * Initializes this workspace.
	 *
	 * If this workspace is brand new, a root node is created automatically.
	 *
	 * @param integer $initializationCause
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject($initializationCause) {
		if ($initializationCause === \F3\FLOW3\Object\Container\ObjectContainerInterface::INITIALIZATIONCAUSE_CREATED) {
			$this->rootNode = $this->objectManager->create('F3\TYPO3CR\Domain\Model\Node', '/', $this);
			$this->objectManager->get('F3\TYPO3CR\Domain\Repository\NodeRepository')->add($this->rootNode);
		}
	}

	/**
	 * Returns the name of this workspace
	 *
	 * @return string Name of this workspace
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the base workspace, if any
	 * 
	 * @return \F3\TYPO3CR\Domain\Model\Workspace
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseWorkspace() {
		return $this->baseWorkspace;
	}

	/**
	 * Returns the root node of this workspace
	 *
	 * @return \F3\TYPO3CR\Domain\Model\Node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRootNode() {
		return $this->rootNode;
	}

	/**
	 * Sets the context from which this workspace was acquired.
	 *
	 * This will be set by the context itself while retrieving the workspace via the
	 * context's getWorkspace() method. The context is transient and therefore needs to be
	 * set on every script run again.
	 *
	 * This method is only for internal use, don't mess with it.
	 *
	 * @param \F3\TYPO3CR\Domain\Service\Context $context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext(\F3\TYPO3CR\Domain\Service\Context $context) {
		$this->context = $context;
		$this->rootNode->setContext($context);
	}

	/**
	 * Returns the current context this workspace operates in.
	 * 
	 * @return \F3\TYPO3CR\Domain\Service\Context
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContext() {
		return $this->context;
	}
}

?>