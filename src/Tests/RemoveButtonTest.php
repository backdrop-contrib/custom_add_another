<?php

namespace Drupal\custom_add_another\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\file\Functional\FileFieldTestBase;

/**
 * Test case for 'Remove' button label alter.
 *
 * @group custom_add_another
 */
class RemoveButtonTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
    'file_module_test',
    'field_ui',
    'custom_add_another',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system manager.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * Tests changes of multiple fields buttons labels.
   */
  function testRemoveButtonLabelAlter() {
    $type_name = 'article';
    $field_name = 'test_file_field';
    $test_file = $this->getTestFile('text');

    // Creating field and checking labels.
    $this->createFileField($field_name, 'node', $type_name, ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED]);
    $this->drupalGet("node/add/$type_name");
    $edit = [
      'files[' . $field_name . '_0][]' => $this->fileSystem->realpath($test_file->getFileUri()),
    ];
    $this->submitForm($edit, t('Upload'));

    $button_name = $field_name . '_0_remove_button';
    $button = $this->assertSession()->buttonExists($button_name);
    $this->assertSame('Remove', $button->getValue());

    // Updating field settings and checking labels again.
    $updated_add_more_value = $this->randomString();
    $updated_remove_value = $this->randomString();
    $this
      ->entityTypeManager
      ->getStorage('field_config')
      ->load('node.' . $type_name . '.' . $field_name)
      ->setThirdPartySetting('custom_add_another', 'custom_add_another', $updated_add_more_value)
      ->setThirdPartySetting('custom_add_another', 'custom_remove', $updated_remove_value)
      ->save();

    $this->drupalGet("node/add/$type_name");
    $edit = ['files[' . $field_name . '_0][]' => $this->fileSystem->realpath($test_file->getFileUri())];
    $this->submitForm($edit, $updated_add_more_value);

    $button = $this->assertSession()->buttonExists($button_name);
    $this->assertSame($updated_remove_value, $button->getValue());
  }

}
