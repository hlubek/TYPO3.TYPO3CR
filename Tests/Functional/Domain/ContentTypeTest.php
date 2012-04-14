<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\TYPO3\Domain\Service\ContentContext;

/**
 * Functional test for testing content type related features
 */
class ContentTypeTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContentTypeManager
	 */
	protected $contentTypeManager;

	/**
	 * Set up a content type manager
	 */
	public function setUp() {
		parent::setUp();

		$this->contentTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContentTypeManager');
	}

	/**
	 * @test
	 */
	public function getLabelUsesConfiguredLabelGenerator() {
		$context = new ContentContext('live');
		$rootNode = $context->getWorkspace()->getRootNode();

		$fooNode = $rootNode->createNode('foo', 'TYPO3.TYPO3CR:TestTypeWithLabel');
		$fooNode->setProperty('specialLabel', 'This should be the label');

		$label = $fooNode->getLabel();
		$this->assertEquals('This should be the label', $label, 'The configured node label generator of the content type should be used');
	}


}
?>