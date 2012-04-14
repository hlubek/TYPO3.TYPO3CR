<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Interface for a Node label generator strategy
 */
interface NodeLabelGeneratorInterface {

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 * @return string
	 */
	public function getLabel(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node);

}
?>
