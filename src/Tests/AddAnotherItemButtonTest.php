<?php

namespace Drupal\custom_add_another\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\field\Functional\FieldTestBase;

/**
 * Test case for 'Add another item' button label alter.
 *
 * @group custom_add_another
 */
class AddAnotherItemButtonTest extends FieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_test',
    'options',
    'entity_test',
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
   * An array of values defining a field with unlimited cardinality.
   *
   * @var array
   */
  protected $fieldStorageUnlimited;

  /**
   * An array of values defining a field.
   *
   * @var array
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser(['view test entity', 'administer entity_test content']);
    $this->drupalLogin($web_user);

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->fieldStorageUnlimited = [
      'field_name' => 'field_unlimited',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];

    $this->field = [
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
      'description' => '[site:name]_description',
      'weight' => mt_rand(0, 127),
      'settings' => [
        'test_field_setting' => $this->randomMachineName(),
      ],
    ];
  }

  /**
   * Tests changes of multiple fields buttons labels.
   */
  function testAddAnotherItemButtonAlter() {
    $field_storage = $this->fieldStorageUnlimited;
    $field_name = $field_storage['field_name'];
    $this->field['field_name'] = $field_name;

    // Creating field with unlimited cardinality
    $this
      ->entityTypeManager
      ->getStorage('field_storage_config')
      ->create($field_storage)
      ->save();

    /** @var \Drupal\field\FieldConfigInterface $field_config_entity */
    $field_config_entity = $this
      ->entityTypeManager
      ->getStorage('field_config')
      ->create($this->field);
    $field_config_entity->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = $this
      ->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($this->field['entity_type'] . '.' . $this->field['bundle'] . '.default');

    if (!$entity_form_display) {
      $entity_form_display = $this
        ->entityTypeManager
        ->getStorage('entity_form_display')
        ->create([
          'targetEntityType' => $this->field['entity_type'],
          'bundle' => $this->field['bundle'],
          'mode' => 'default',
          'status' => TRUE,
        ]);
    }

    $entity_form_display
      ->setComponent($field_name)
      ->save();

    // Checking field label.
    $button_name = $field_name . '_add_more';

    $default_value = 'Add another item';
    $this->drupalGet('entity_test/add');
    $button = $this->assertSession()->buttonExists($button_name);
    $this->assertSame($default_value, $button->getValue());

    // Updating label and checking again.
    $updated_value = $this->randomString();
    $field_config_entity
      ->setThirdPartySetting('custom_add_another', 'custom_add_another', $updated_value)
      ->save();
    $this->drupalGet('entity_test/add');
    $button = $this->assertSession()->buttonExists($button_name);
    $this->assertSame($updated_value, $button->getValue());
  }

}
