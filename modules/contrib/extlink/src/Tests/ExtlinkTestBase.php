<?php

namespace Drupal\extlink\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for External Link tests.
 *
 * Provides common setup stuff and various helper functions
 */
abstract class ExtlinkTestBase extends WebTestBase {
	
  public static $modules = array('extlink');
  
  /**
   * User with various administrative permissions.
   * @var Drupal user
   */
  protected $admin_user;

  /**
   * Normal visitor with limited permissions
   * @var Drupal user;
   */
  protected $normal_user;
  
  /**
   * Drupal path of the (general) External Links admin page
   */
  const EXTLINK_ADMIN_PATH = 'admin/config/user-interface/extlink';
  
  /**
   * {@inheritdoc}
   */
  function setUp() {
    // Enable any module that you will need in your tests.
    parent::setUp();
    // Create a normal user.
    $permissions = array();
    $this->normal_user = $this->drupalCreateUser($permissions);

    // Create an admin user.
    $permissions[] = 'administer site configuration';
    $permissions[] = 'administer permissions';
    $this->admin_user = $this->drupalCreateUser($permissions);
  }
  
  protected function getNodeFormValues() {
    $edit = array(
      'title' => 'node_title ' . $this->randomName(32),
      'body[' . LANGUAGE_NONE . '][0][value]' => 'node_body ' . $this->randomName(256) . ' <a href="http://google.com">Google!</a>',
    );
    return $edit;
  }
  
  /**
   * Test if External Link is present
   */
  protected function assertExternalLinkPresence() {
    $elements = $this->xpath('//span[@class="ext"]');
    if (count($elements) > 0)
       $this->pass('There should be an External Link on the form.', 'External Links');
    else
       $this->fail('There should be an External Link on the form.', 'External Links');
  }
}