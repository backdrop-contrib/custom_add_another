<?php

namespace Drupal\custom_add_another\Tests\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests that custom_add_another settings are set when other settings exist.
 *
 * @group custom_add_another
 */
class CustomAddAnotherThirdPartySettingsTest extends BrowserTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'custom_add_another',
    'custom_add_another_conflicting_test',
  ];

  /**
   * {@inheritDoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer content types and fields.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a multi-value text field.
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'field_name' => 'field_test',
        'entity_type' => 'node',
        'type' => 'string',
        'cardinality' => -1,
      ])
      ->save();
    $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_name' => 'field_test',
        'label' => 'Test Field',
        'entity_type' => 'node',
        'bundle' => 'article',
      ])
      ->save();

    // Create admin user with necessary permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
    ]);

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that custom_add_another settings persist when other settings exist.
   */
  public function testThirdPartySettingsNotOverwritten(): void {
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_test');

    // Verify the custom_add_another and test module form elements are present.
    $this->assertSession()->fieldExists('custom_add_another');
    $this->assertSession()->fieldExists('custom_remove');
    $this->assertSession()->fieldExists('third_party_settings[custom_add_another_conflicting_test][test]');

    // Set some labels for custom_add_another via the form.
    $edit = [
      'custom_add_another' => 'Add',
      'custom_remove' => 'Remove',
    ];
    $this->submitForm($edit, 'Save settings');

    // Verify the settings were saved by checking the success message.
    $this->assertSession()->statusMessageContains('Saved Test Field configuration.', 'status');

    // Load the config and verify the third_party_settings were set correctly.
    $field_config = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->load('node.article.field_test');
    $this->assertSame(
      [
        'custom_add_another' => 'Add',
        'custom_remove' => 'Remove',
      ],
      [
        'custom_add_another' => $field_config->getThirdPartySetting('custom_add_another', 'custom_add_another'),
        'custom_remove' => $field_config->getThirdPartySetting('custom_add_another', 'custom_remove'),
      ],
      'Expected custom_add_another settings to be preserved, but they were overwritten.'
    );
  }

}
