<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

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
 * A test Node label generator
 */
class TestTypeLabelGenerator implements \TYPO3\TYPO3CR\Domain\Model\NodeLabelGeneratorInterface {

	/**
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return string
	 */
	public function getLabel(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$options = $node->getContentTypeModel()->getNodeLabelGeneratorOptions();
		if (isset($options['propertyName'])) {
			return $node->getProperty($options['propertyName']);
		}
		return NULL;
	}

}
?>