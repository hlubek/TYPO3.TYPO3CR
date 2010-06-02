<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3CR\Version;

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
 * A VersionHistory object wraps an nt:versionHistory node. It provides
 * convenient access to version history information.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class VersionHistory extends \F3\TYPO3CR\Node implements \F3\PHPCR\Version\VersionHistoryInterface {

	/**
	 * @var string
	 */
	protected $rootVersion;

	/**
	 * @var array
	 */
	protected $versionLabels = array();

	/**
	 *
	 * @param array $rawData
	 * @param \F3\PHPCR\SessionInterface $session
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @param \F3\PHPCR\NodeInterface $node 
	 * @author Tamas Ilsinszki <ilsinszkitamas@yahoo.com>
	 * @todo: Root version should not have a parent node in any workspace, however this is not allowed currently.
	 */
	public function __construct(array $rawData = array(), \F3\PHPCR\SessionInterface $session, \F3\FLOW3\Object\ObjectManagerInterface $objectManager, \F3\PHPCR\NodeInterface $node) {
		parent::__construct($rawData, $session, $objectManager);

		$this->session->getNode($this->getPath())->setProperty('jcr:versionableUuid', $node->getIdentifier(), \F3\PHPCR\PropertyType::REFERENCE);

		$rawRootVersionData = array(
			'parent' => $this->getIdentifier(),
			'nodetype' => 'nt:version',
			'name' => $this->getName()
		);
		$this->rootVersion = $this->objectManager->create('F3\TYPO3CR\Version\Version', $rawRootVersionData, $this->session);
	}

	/**
	 * Returns the identifier of the versionable node for which this is the
	 * version history.
	 *
	 * @return string the identifier of the versionable node for which this is the version history.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getVersionableIdentifier() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322039);
	}

	/**
	 * Returns the root version of this version history.
	 *
	 * @return \F3\PHPCR\Version\VersionInterface a Version object.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 * @author Tamas Ilsinszki <ilsinszkitamas@yahoo.com>
	 */
	public function getRootVersion() {
		return $this->rootVersion;
	}

	/**
	 * This method returns an iterator over all the versions in the line of
	 * descent from the root version to that base version within this history
	 * that is bound to the workspace through which this VersionHistory was
	 * accessed.
	 *
	 * Within a version history H, B is the base version bound to workspace W
	 * if and only if there exists a versionable node N in W whose version
	 * history is H and B is the base version of N.
	 *
	 * The line of descent from version V1 to V2, where V2 is a successor of V1,
	 * is the ordered list of versions starting with V1 and proceeding through
	 * each direct successor to V2.
	 *
	 * The versions are returned in order of creation date, from oldest to newest.
	 *
	 * Note that in a simple versioning repository the behavior of this method is
	 * equivalent to returning all versions in the version history in order from
	 * oldest to newest.
	 *
	 * @return \F3\PHPCR\Version\VersionIteratorInterface a VersionIterator object.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getAllLinearVersions() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322041);
	}

	/**
	 * Returns an iterator over all the versions within this version history.
	 * If the version graph of this history is linear then the versions are
	 * returned in order of creation date, from oldest to newest. Otherwise the
	 * order of the returned versions is implementation-dependent.
	 *
	 * @return \F3\PHPCR\Version\VersionIteratorInterface a VersionIterator object.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getAllVersions() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322042);
	}

	/**
	 * This method returns all the frozen nodes of all the versions in this
	 * verison history in the same order as getAllLinearVersions().
	 *
	 * @return \F3\PHPCR\NodeIteratorInterface a NodeIterator object.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getAllLinearFrozenNodes() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322043);
	}

	/**
	 * Returns an iterator over all the frozen nodes of all the versions of
	 * this version history. Under simple versioning the order of the returned
	 * nodes will be the order of their creation. Under full versioning the
	 * order is implementation-dependent.
	 *
	 * @return \F3\PHPCR\NodeIteratorInterface a NodeIterator object.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getAllFrozenNodes() {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322044);
	}

	/**
	 * Retrieves a particular version from this version history by version name.
	 *
	 * @param string $versionName a version name
	 * @return \F3\PHPCR\Version\VersionInterface a Version object.
	 * @throws \F3\PHPCR\Version\VersionException if the specified version is not in this version history.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getVersion($versionName) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322045);
	}

	/**
	 * Retrieves a particular version from this version history by version label.
	 *
	 * @param string $label a version label
	 * @return \F3\PHPCR\Version\VersionInterface a Version object.
	 * @throws \F3\PHPCR\Version\VersionException if the specified label is not in this version history.
	 * @throws \F3\PHPCR\RepositoryException if an error occurs.
	 */
	public function getVersionByLabel($label) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322046);
	}

	/**
	 * Adds the specified label to the specified version.
	 * The label must be a JCR name in either qualified or extended form
	 * and therefore must conform to the syntax restriction that apply to
	 * such names. In particular a colon (:) should not be used unless
	 * it is intended as a prefix delimiter in a qualified name.
	 *
	 * Adding a version label to a version corresponds to
	 * adding a reference property with a name specified by the label parameter
	 * to the jcr:versionLabels sub node of the nt:versionHistory node. The
	 * reference property points to the nt:version node that represents the
	 * specified version.
	 *
	 * This is a workspace-write method and therefore the change is made
	 * immediately.
	 *
	 * Within a particular version history, a given label may appear a maximum of
	 * once. If the specified label is already assigned to a version in this
	 * history and moveLabel is true then the label is removed from its current
	 * location and added to the version with the specified versionName. If m
	 * oveLabel is false, then an attempt to add a label that already exists
	 * will fail.
	 *
	 * A VersionException is thrown if the named version is not in this
	 * VersionHistory or if it is the root version (jcr:rootVersion) or if the
	 * label specified is not a valid JCR NAME.
	 *
	 * @param string $versionName the name of the version to which the label is to be added.
	 * @param string $label the label to be added, a JCR name in either extended or qualified form.
	 * @param boolean $moveLabel if true, then if label is already assigned to a version in this version history, it is moved to the new version specified; if false, then attempting to assign an already used label will throw a LabelExistsVersionException.
	 * @return void
	 * @throws \F3\PHPCR\Version\LabelExistsVersionException if moveLabel is false, and an attempt is made to add a label that already exists in this version history
	 * @throws \F3\PHPCR\Version\VersionException if the specified version does not exist in this version history or if the specified version is the root version (jcr:rootVersion).
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 */
	public function addVersionLabel($versionName, $label, $moveLabel) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322047);
	}

	/**
	 * Removes the specified label from among the labels of this version history.
	 * The label must be a JCR name in either qualified or extended form.
	 * This corresponds to removing a property from the jcr:versionLabels child node
	 * of the nt:versionHistory node that represents this version history.
	 *
	 * This is a workspace-write method and therefore the change is made
	 * immediately.
	 *
	 * @param string $label a version label. A JCR name in either extended or qualified form.
	 * @return void
	 * @throws \F3\PHPCR\Version\VersionException if the name label does not exist in this version history.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 */
	public function removeVersionLabel($label) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322048);
	}

	/**
	 * Returns true if the given version has the given label. If no $version is given
	 * returns true if any version in the history has the given label.
	 * The label must be a JCR name in either qualified or extended form.
	 *
	 * @param string $label a version label. A JCR name in either extended or qualified form.
	 * @param \F3\PHPCR\Version\VersionInterface $version a Version object
	 * @return boolean a boolean.
	 * @throws \F3\PHPCR\Version\VersionException if the specified version is not of this version history.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 */
	public function hasVersionLabel($label, $version = NULL) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322049);
	}

	/**
	 * Returns all version labels of the given version - empty array if none.
	 * If a $version is given returns all version labels of the history or an empty
	 * array if there are none.
	 *
	 * @param VersionInterface $version a Version object
	 * @return array a string array containing all the labels of the (given) version (history)
	 * @throws \F3\PHPCR\Version\VersionException if the specified version is not in this version history.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 */
	public function getVersionLabels($version = NULL) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322050);
	}

	/**
	 * Removes the named version from this version history and automatically
	 * repairs the version graph. If the version to be removed is V, V's
	 * predecessor set is P and V's successor set is S, then the version graph
	 * is repaired s follows:
	 * * For each member of P, remove the reference to V from its successor list
	 *   and add references to each member of S.
	 * * For each member of S, remove the reference to V from its predecessor
	 *   list and add references to each member of P.
	 *
	 * Note that this change is made immediately; there is no need to call save.
	 * In fact, since the the version storage is read-only with respect to normal
	 * repository methods, save does not even function in this context.
	 *
	 * @param string $versionName the name of a version in this version history.
	 * @return void
	 * @throws \F3\PHPCR\ReferentialIntegrityException if the specified version is currently the target of a REFERENCE property elsewhere in the repository (not necessarily in this workspace) and the current Session has read access to that REFERENCE property.
	 * @throws \F3\PHPCR\AccessDeniedException if the current Session does not have permission to remove the specified version or if the specified version is currently the target of a REFERENCE property elsewhere in the repository (not just in this workspace) and the current Session does not have read access to that REFERENCE property.
	 * @throws \F3\PHPCR\UnsupportedRepositoryOperationException if this operation is not supported by the implementation.
	 * @throws \F3\PHPCR\Version\VersionException if the named version is not in this version history.
	 * @throws \F3\PHPCR\RepositoryException if another error occurs.
	 */
	public function removeVersion($versionName) {
		throw new \F3\PHPCR\UnsupportedRepositoryOperationException('Method not yet implemented, sorry!', 1245322051);
	}

}
?>
