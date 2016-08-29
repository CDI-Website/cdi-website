<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Render\Markup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\contact\Entity\Message;

/**
 * Tests field tokens.
 *
 * @group token
 */
class FieldTest extends KernelTestBase {

  /**
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $testFormat;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'text', 'field', 'filter', 'contact', 'options'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create the article content type with a text field.
    $node_type = NodeType::create([
      'type' => 'article',
    ]);
    $node_type->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'type' => 'text',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Test field',
    ]);
    $field->save();

    // Create a reference field with the same name on user.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'user',
      'type' => 'entity_reference',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Test field',
    ]);
    $field->save();

    $this->testFormat = FilterFormat::create([
      'format' => 'test',
      'weight' => 1,
      'filters' => [
        'filter_html_escape' => ['status' => TRUE],
      ],
    ]);
    $this->testFormat->save();

    // Create a multi-value list_string field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_list',
      'entity_type' => 'node',
      'type' => 'list_string',
      'cardinality' => 2,
      'settings' => [
        'allowed_values' => [
          'key1' => 'value1',
          'key2' => 'value2',
        ]
      ],
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_name' => 'test_list',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
  }

  /**
   * Tests [entity:field_name] tokens.
   */
  public function testEntityFieldTokens() {
    // Create a node with a value in its fields and test its tokens.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
      'test_field' => [
        'value' => 'foo',
        'format' => $this->testFormat->id(),
      ],
      'test_list' => [
        'value1',
        'value2',
      ],
    ]);
    $entity->save();
    $this->assertTokens('node', ['node' => $entity], [
      'test_field' => Markup::create('foo'),
      'test_field:0' => Markup::create('foo'),
      'test_field:0:value' => 'foo',
      'test_field:value' => 'foo',
      'test_field:0:format' => $this->testFormat->id(),
      'test_field:format' => $this->testFormat->id(),
      'test_list:0' => Markup::create('value1'),
      'test_list:1' => Markup::create('value2'),
      'test_list:0:value' => Markup::create('value1'),
      'test_list:value' => Markup::create('value1'),
      'test_list:1:value' => Markup::create('value2'),
    ]);

    // Verify that no third token was generated for the list_string field.
    $this->assertNoTokens('node', ['node' => $entity], [
      'test_list:2',
      'test_list:2:value',
    ]);

    // Test the test_list token metadata.
    $tokenService = \Drupal::service('token');
    $token_info = $tokenService->getTokenInfo('node', 'test_list');
    $this->assertEqual($token_info['name'], 'test_list');
    $this->assertEqual($token_info['module'], 'token');
    $this->assertEqual($token_info['type'], 'list<node-test_list>');
    $typeInfo = $tokenService->getTypeInfo('list<node-test_list>');
    $this->assertEqual($typeInfo['name'], 'List of test_list values');
    $this->assertEqual($typeInfo['type'], 'list<node-test_list>');

    // Create a node without a value in the text field and test its token.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
    ]);
    $entity->save();

    $this->assertNoTokens('node', ['node' => $entity], [
      'test_field',
    ]);
  }

  /**
   * Tests the token metadata for a field token.
   */
  public function testFieldTokenInfo() {
    /** @var \Drupal\token\Token $tokenService */
    $tokenService = \Drupal::service('token');

    // Test the token info of the text field of the artcle content type.
    $token_info = $tokenService->getTokenInfo('node', 'test_field');
    $this->assertEqual($token_info['name'], 'Test field', 'The token info name is correct.');
    $this->assertEqual($token_info['description'], 'Text (formatted) field.', 'The token info description is correct.');
    $this->assertEqual($token_info['module'], 'token', 'The token info module is correct.');

    // Now create two more content types that share the field but the last
    // of them sets a different label. This should show an alternative label
    // at the token info.
    $node_type = NodeType::create([
      'type' => 'article2',
    ]);
    $node_type->save();
    $field = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'bundle' => 'article2',
      'label' => 'Test field',
    ]);
    $field->save();

    $node_type = NodeType::create([
      'type' => 'article3',
    ]);
    $node_type->save();
    $field = FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'bundle' => 'article3',
      'label' => 'Different test field',
    ]);
    $field->save();

    $token_info = $tokenService->getTokenInfo('node', 'test_field');
    $this->assertEqual($token_info['name'], 'Test field', 'The token info name is correct.');
    $this->assertEqual((string) $token_info['description'], 'Text (formatted) field. Also known as <em class="placeholder">Different test field</em>.', 'When a field is used in several bundles with different labels, this is noted at the token info description.');
    $this->assertEqual($token_info['module'], 'token', 'The token info module is correct.');
    $this->assertEqual($token_info['type'], 'node-test_field', 'The field property token info type is correct.');

    // Test field property token info.
    $token_info = $tokenService->getTokenInfo('node-test_field', 'value');
    $this->assertEqual($token_info['name'], 'Text', 'The field property token info name is correct.');
    // This particular field property description happens to be empty.
    $this->assertEqual((string) $token_info['description'], '', 'The field property token info description is correct.');
    $this->assertEqual($token_info['module'], 'token', 'The field property token info module is correct.');
  }

  /**
   * Test tokens on node with the token view mode overriding default formatters.
   */
  public function testTokenViewMode() {
    $value = 'A really long string that should be trimmed by the special formatter on token view we are going to have.';

    // The formatter we are going to use will eventually call Unicode::strlen.
    // This expects that the Unicode has already been explicitly checked, which
    // happens in DrupalKernel. But since that doesn't run in kernel tests, we
    // explicitly call this here.
    Unicode::check();

    // Create a node with a value in the text field and test its token.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
      'test_field' => [
        'value' => $value,
        'format' => $this->testFormat->id(),
      ],
    ]);
    $entity->save();

    $this->assertTokens('node', ['node' => $entity], [
      'test_field' => Markup::create($value),
    ]);

    // Now, create a token view mode which sets a different format for
    // test_field. When replacing tokens, this formatter should be picked over
    // the default formatter for the field type.
    // @see field_tokens().
    $view_mode = EntityViewMode::create([
      'id' => 'node.token',
      'targetEntityType' => 'node',
    ]);
    $view_mode->save();
    $entity_display = entity_get_display('node', 'article', 'token');
    $entity_display->setComponent('test_field', [
      'type' => 'text_trimmed',
      'settings' => [
        'trim_length' => 50,
      ]
    ]);
    $entity_display->save();

    $this->assertTokens('node', ['node' => $entity], [
      'test_field' => Markup::create(substr($value, 0, 50)),
    ]);
  }

  /**
   * Test that tokens are properly created for an entity's base fields.
   */
  public function testBaseFieldTokens() {
    // Create a new contact_message entity and verify that tokens are generated
    // for its base fields. The contact_message entity type is used because it
    // provides no tokens by default.
    $contact_form = ContactForm::create([
      'id' => 'form_id',
    ]);
    $contact_form->save();

    $entity = Message::create([
      'contact_form' => 'form_id',
      'uuid' => '123',
      'langcode' => 'en',
      'name' => 'Test name',
      'mail' => 'Test mail',
      'subject' => 'Test subject',
      'message' => 'Test message',
      'copy' => FALSE,
    ]);
    $entity->save();
    $this->assertTokens('contact_message', ['contact_message' => $entity], [
      'uuid' => Markup::create('123'),
      'langcode' => Markup::create('English'),
      'name' => Markup::create('Test name'),
      'mail' => Markup::create('Test mail'),
      'subject' => Markup::create('Test subject'),
      'message' => Markup::create('Test message'),
      'copy' => 'Off',
    ]);

    // Test the metadata of one of the tokens.
    $tokenService = \Drupal::service('token');
    $token_info = $tokenService->getTokenInfo('contact_message', 'subject');
    $this->assertEquals($token_info['name'], 'Subject');
    $this->assertEquals($token_info['description'], 'Text (plain) field.');
    $this->assertEquals($token_info['module'], 'token');

    // Verify that node entity type doesn't have a uid token.
    $this->assertNull($tokenService->getTokenInfo('node', 'uid'));
  }
}
