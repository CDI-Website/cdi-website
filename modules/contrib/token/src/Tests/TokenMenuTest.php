<?php

namespace Drupal\token\Tests;

use Drupal\node\Entity\Node;

/**
 * Tests menu tokens.
 *
 * @group token
 */
class TokenMenuTest extends TokenTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['menu_ui', 'node'];

  function testMenuTokens() {
    // Make sure we have a body field on the node type.
    $this->drupalCreateContentType(['type' => 'page']);
    // Add a menu.
    $menu = entity_create('menu', array(
      'id' => 'main-menu',
      'label' => 'Main menu',
      'description' => 'The <em>Main</em> menu is used on many sites to show the major sections of the site, often in a top navigation bar.',
    ));
    $menu->save();
    // Add a root link.
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $root_link */
    $root_link = entity_create('menu_link_content', array(
      'link' => ['uri' => 'internal:/admin'],
      'title' => 'Administration',
      'menu_name' => 'main-menu',
    ));
    $root_link->save();

    // Add another link with the root link as the parent.
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $parent_link */
    $parent_link = entity_create('menu_link_content', array(
      'link' => ['uri' => 'internal:/admin/config'],
      'title' => 'Configuration',
      'menu_name' => 'main-menu',
      'parent' => $root_link->getPluginId(),
    ));
    $parent_link->save();

    // Test menu link tokens.
    $tokens = array(
      'id' => $parent_link->getPluginId(),
      'title' => 'Configuration',
      'menu' => 'Main menu',
      'menu:name' => 'Main menu',
      'menu:machine-name' => $menu->id(),
      'menu:description' => 'The <em>Main</em> menu is used on many sites to show the major sections of the site, often in a top navigation bar.',
      'menu:menu-link-count' => '2',
      'menu:edit-url' => \Drupal::url('entity.menu.edit_form', ['menu' => 'main-menu'], array('absolute' => TRUE)),
      'url' => \Drupal::url('system.admin_config', [], array('absolute' => TRUE)),
      'url:absolute' => \Drupal::url('system.admin_config', [], array('absolute' => TRUE)),
      'url:relative' => \Drupal::url('system.admin_config', [], array('absolute' => FALSE)),
      'url:path' => '/admin/config',
      'url:alias' => '/admin/config',
      'edit-url' => \Drupal::url('entity.menu_link_content.canonical', ['menu_link_content' => $parent_link->id()], array('absolute' => TRUE)),
      'parent' => 'Administration',
      'parent:id' => $root_link->getPluginId(),
      'parent:title' => 'Administration',
      'parent:menu' => 'Main menu',
      'parent:parent' => NULL,
      'parents' => 'Administration',
      'parents:count' => 1,
      'parents:keys' => $root_link->getPluginId(),
      'root' => 'Administration',
      'root:id' => $root_link->getPluginId(),
      'root:parent' => NULL,
      'root:root' => NULL,
    );
    $this->assertTokens('menu-link', array('menu-link' => $parent_link), $tokens);

    // Add a node.
    $node = $this->drupalCreateNode();

    // Allow main menu for this node type.
    //$this->config('menu.entity.node.' . $node->getType())->set('available_menus', array('main-menu'))->save();

    // Add a node menu link.
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $node_link */
    $node_link = entity_create('menu_link_content', array(
      'link' => ['uri' =>'entity:node/' . $node->id()],
      'title' => 'Node link',
      'parent' => $parent_link->getPluginId(),
      'menu_name' => 'main-menu',
    ));
    $node_link->save();

    // Test [node:menu] tokens.
    $tokens = array(
      'menu-link' => 'Node link',
      'menu-link:id' => $node_link->getPluginId(),
      'menu-link:title' => 'Node link',
      'menu-link:menu' => 'Main menu',
      'menu-link:url' => $node->url('canonical', ['absolute' => TRUE]),
      'menu-link:url:path' => '/node/' . $node->id(),
      'menu-link:edit-url' => $node_link->url('edit-form', ['absolute' => TRUE]),
      'menu-link:parent' => 'Configuration',
      'menu-link:parent:id' => $parent_link->getPluginId(),
      'menu-link:parents' => 'Administration, Configuration',
      'menu-link:parents:count' => 2,
      'menu-link:parents:keys' => $root_link->getPluginId() . ', ' . $parent_link->getPluginId(),
      'menu-link:root' => 'Administration',
      'menu-link:root:id' => $root_link->getPluginId(),
    );
    $this->assertTokens('node', array('node' => $node), $tokens);

    // Reload the node which will not have $node->menu defined and re-test.
    $loaded_node = Node::load($node->id());
    $this->assertTokens('node', array('node' => $loaded_node), $tokens);

    // Regression test for http://drupal.org/node/1317926 to ensure the
    // original node object is not changed when calling menu_node_prepare().
    $this->assertTrue(!isset($loaded_node->menu), t('The $node->menu property was not modified during token replacement.'), 'Regression');

    // Now add a node with a menu-link from the UI and ensure it works.
    $this->drupalLogin($this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'administer menu',
      'administer nodes',
      'administer content types',
      'access administration pages',
    ]));
    // Setup node type menu options.
    $edit = array(
      'menu_options[main-menu]' => 1,
      'menu_options[main]' => 1,
      'menu_parent' => 'main-menu:',
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));

    // Use a menu-link token in the body.
    $this->drupalGet('node/add/page');
    $this->drupalPostForm(NULL, [
      // This should get replaced on save.
      // @see token_module_test_node_presave()
      'title[0][value]' => 'Node menu title test',
      'body[0][value]' => 'This is a [node:menu-link:title] token to the menu link title',
      'menu[enabled]' => 1,
      'menu[title]' => 'Test preview',
    ], t('Save and publish'));
    $node = $this->drupalGetNodeByTitle('Node menu title test');
    $this->assertEqual('This is a Test preview token to the menu link title', $node->body->value);

    // Now test a parent link and token.
    $this->drupalGet('node/add/page');
    // Make sure that the previous node save didn't result in two menu-links
    // being created by the computed menu-link ER field.
    // @see token_entity_base_field_info()
    // @see token_node_menu_link_submit()
    $selects = $this->cssSelect('select[name="menu[menu_parent]"]');
    $select = reset($selects);
    $options = $this->getAllOptions($select);
    // Filter to items with title containing 'Test preview'.
    $options = array_filter($options, function(\SimpleXMLElement $item) {
      return strpos((string) $item[0], 'Test preview') !== FALSE;
    });
    $this->assertEqual(1, count($options));
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Node menu title parent path test',
      'body[0][value]' => 'This is a [node:menu-link:parent:url:path] token to the menu link parent',
      'menu[enabled]' => 1,
      'menu[title]' => 'Child link',
      'menu[menu_parent]' => 'main-menu:' .  $parent_link->getPluginId(),
    ], t('Save and publish'));
    $node = $this->drupalGetNodeByTitle('Node menu title parent path test');
    $this->assertEqual('This is a /admin/config token to the menu link parent', $node->body->value);

    // Now edit the node and update the parent and title.
    $this->drupalPostForm('node/' . $node->id() . '/edit', [
      'menu[menu_parent]' => 'main-menu:' .  $node_link->getPluginId(),
      'title[0][value]' => 'Node menu title edit parent path test',
      'body[0][value]' => 'This is a [node:menu-link:parent:url:path] token to the menu link parent',
    ], t('Save and keep published'));
    $node = $this->drupalGetNodeByTitle('Node menu title edit parent path test', TRUE);
    $this->assertEqual(sprintf('This is a /node/%d token to the menu link parent', $loaded_node->id()), $node->body->value);

    // Make sure that the previous node edit didn't result in two menu-links
    // being created by the computed menu-link ER field.
    // @see token_entity_base_field_info()
    // @see token_node_menu_link_submit()
    $this->drupalGet('node/add/page');
    $selects = $this->cssSelect('select[name="menu[menu_parent]"]');
    $select = reset($selects);
    $options = $this->getAllOptions($select);
    // Filter to items with title containing 'Test preview'.
    $options = array_filter($options, function(\SimpleXMLElement $item) {
      return strpos((string) $item[0], 'Child link') !== FALSE;
    });
    $this->assertEqual(1, count($options));

    // Now add a new node with no menu.
    $this->drupalGet('node/add/page');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Node menu adding menu later test',
      'body[0][value]' => 'Going to add a menu link on edit',
      'menu[enabled]' => 0,
    ], t('Save and publish'));
    $node = $this->drupalGetNodeByTitle('Node menu adding menu later test');
    // Now edit it and add a menu item.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Node menu adding menu later test',
      'body[0][value]' => 'This is a [node:menu-link:parent:url:path] token to the menu link parent',
      'menu[enabled]' => 1,
      'menu[title]' => 'Child link',
      'menu[menu_parent]' => 'main-menu:' .  $parent_link->getPluginId(),
    ], t('Save and keep published'));
    $node = $this->drupalGetNodeByTitle('Node menu adding menu later test', TRUE);
    $this->assertEqual('This is a /admin/config token to the menu link parent', $node->body->value);
    // And make sure the menu link exists with the right URI.
    $link = menu_ui_get_menu_link_defaults($node);
    $this->assertTrue(!empty($link['entity_id']));
    $query = \Drupal::entityQuery('menu_link_content')
      ->condition('link.uri', 'entity:node/' . $node->id())
      ->sort('id', 'ASC')
      ->range(0, 1);
    $result = $query->execute();
    $this->assertTrue($result);
  }

}
