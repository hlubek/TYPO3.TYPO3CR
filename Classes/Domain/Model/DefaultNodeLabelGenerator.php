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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A default Node label generator
 */
class DefaultNodeLabelGenerator implements NodeLabelGeneratorInterface {

	/**
	 * Generate a default label for a node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string
	 */
	public function getLabel(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$label = $node->hasProperty('title') ? strip_tags($node->getProperty('title')) : '(' . $node->getContentType() . ') '. $node->getName();
		$croppedLabel = \TYPO3\FLOW3\Utility\Unicode\Functions::substr($label, 0, Node::LABEL_MAXIMUM_CHARACTERS);
		return $croppedLabel . (strlen($croppedLabel) < strlen($label) ? ' …' : '');
	}

}
?>