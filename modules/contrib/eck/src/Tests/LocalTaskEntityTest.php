<?php

namespace Drupal\eck\Tests;

use Drupal\Core\Url;

/**
 * Tests the local task links in entities.
 *
 * @group eck
 *
 * @codeCoverageIgnore because we don't have to test the tests
 */
class LocalTaskEntityTest extends TestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'eck', 'block'];

  /**
   * @var
   */
  protected $entityType;

  /**
   * @var
   */
  protected $bundle;

  public function setUp() {
    parent::setUp();
    $this->entityType = $this->createEntityType();
    $this->bundle = $this->createEntityBundle($this->entityType['id']);

    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests that the entity contains the local task links.
   */
  public function testLocalTask() {
    $edit['title[0][value]'] = $this->randomMachineName();
    $route_args = [
      'eck_entity_type' => $this->entityType['id'],
      'eck_entity_bundle' =>  $this->bundle['type'],
    ];
    $this->drupalPostForm(Url::fromRoute("eck.entity.add", $route_args), $edit, t('Save'));

    $route_args = [
        $this->entityType['id'] => 1,
    ];
    $this->assertLocalTasksFor("entity.{$this->entityType['id']}.canonical", $route_args);
    $this->assertLocalTasksFor("entity.{$this->entityType['id']}.edit_form", $route_args);
    $this->assertLocalTasksFor("entity.{$this->entityType['id']}.delete_form", $route_args);
  }

  /**
   * Go to a page and check if exist the local task links.
   * @param string $route
   * @param array $routeArguments
   */
  protected function assertLocalTasksFor($route, array $routeArguments) {
    $this->drupalGet(Url::fromRoute($route, $routeArguments));
    $this->assertLocalTaskLinkRoute("entity.{$this->entityType['id']}.canonical", $routeArguments, 'View');
    $this->assertLocalTaskLinkRoute("entity.{$this->entityType['id']}.edit_form", $routeArguments, 'Edit');
    $this->assertLocalTaskLinkRoute("entity.{$this->entityType['id']}.delete_form", $routeArguments, 'Delete');
  }

  /**
   * Pass if a link with the specified label and href is found.
   *
   * @param string $route
   *   The route name.
   * @param array $route_args
   *   The route arguments.
   * @param string $label
   *   Text between the anchor tags.
   */
  protected function assertLocalTaskLinkRoute($route, array $route_args, $label) {
    $url = Url::fromRoute($route, $route_args);
    $links = $this->xpath('//ul/li/a[contains(@href, :href) and normalize-space(text())=:label]', [':href' => $url->toString(), ':label' => $label]);

    $this->assert(count($links) == 1, t('Link with label %label found and its route is :route', [':route' => $route, '%label' => $label]));
  }
}
