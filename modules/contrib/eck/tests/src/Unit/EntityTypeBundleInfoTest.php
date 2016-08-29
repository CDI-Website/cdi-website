<?php
/**
 * @file
 * Contains Drupal\Tests\eck\Unit\EntityTypeBundleInfoTest.
 */

namespace Drupal\Tests\eck\Unit;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\eck\EckEntityTypeBundleInfo;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Tests the form element implementation.
 *
 * @group eck
 */
class EntityTypeBundleInfoTest extends UnitTestBase {

  protected $entityTypeManagerMock;
  protected $languageManagerMock;
  protected $moduleHandlerMock;
  protected $typedDataManagerMock;
  protected $cacheBackendMock;

  /**
   * @test
   */
  public function returnsFalseWhenNonExistingEntityTypeIsPassed() {
    $sut = $this->createNewTestSubject();
    $this->assertFalse($sut->entityTypeHasBundles('does not exist'));
  }

  /**
   * @test
   */
  public function returnsFalseWhenEntityTypeHasNoBundles() {
    $sut = $this->createNewTestSubjectWithEntityType();
    $this->assertFalse($sut->entityTypeHasBundles('existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsTrueWhenEntityTypeHasAtLeastOneBundle() {
    $sut = $this->createNewTestSubjectWithEntityTypeAndBundles();
    $this->assertTrue($sut->entityTypeHasBundles('existing_entity_type'));
  }

  /**
   * @test
   */
  public function cachesData() {
    $this->cacheBackendMock = $this->cacheBackendMock = $this->getMockForAbstractClass(CacheBackendInterface::class);
    $this->cacheBackendMock->expects($this->once())->method('set');
    $sut = $this->createNewTestSubject();
    $sut->entityTypeHasBundles('test');
  }

  /**
   * @test
   */
  public function usesCachedDataWhenAvailable() {
    $this->cacheBackendMock = $this->cacheBackendMock = $this->getMockForAbstractClass(CacheBackendInterface::class);
    $this->cacheBackendMock->expects($this->once())->method('get')->willReturn((object) ['data' =>'test']);

    $sut = $this->createNewTestSubject();
    $this->assertSame('test', $sut->getAllBundleInfo());
  }

  /**
   * @test
   */
  public function returnsNoMachineNamesIfEntityTypeDoesNotExist() {
    $sut = $this->createNewTestSubject();
    $this->assertSame([], $sut->getEntityTypeBundleMachineNames('non_existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsNoMachineNamesIfEntityTypeHasNoBundles() {
    $sut = $this->createNewTestSubjectWithEntityType();
    $this->assertSame([], $sut->getEntityTypeBundleMachineNames('existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsMachineNamesIfEntityTypeHasBundles() {
    $sut = $this->createNewTestSubjectWithEntityTypeAndBundles();
    $this->assertNotEmpty($sut->getEntityTypeBundleMachineNames('existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsZeroIfEntityTypeDoesNotExist() {
    $sut = $this->createNewTestSubject();
    $this->assertEquals(0, $sut->entityTypeBundleCount('non_existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsZeroIfEntityTypeHasNoBundles() {
    $sut = $this->createNewTestSubjectWithEntityType();
    $this->assertEquals(0, $sut->entityTypeBundleCount('existing_entity_type'));
  }

  /**
   * @test
   */
  public function correctlyCountsEntityTypeBundles() {
    for ($i = 1; $i <= 10; $i++) {
      $sut = $this->createNewTestSubjectWithEntityTypeAndBundles($i);
      $this->assertEquals($i, $sut->entityTypeBundleCount('existing_entity_type'));
    }
  }

  /**
   * @return EckEntityTypeBundleInfo
   */
  protected function createNewTestSubject() {
    if (!isset($this->entityTypeManagerMock)) {
      $this->entityTypeManagerMock = $this->getMockForAbstractClass(EntityTypeManagerInterface::class);
      $this->entityTypeManagerMock->method('getDefinitions')->willReturn([]);
    }
    if (!isset($this->languageManagerMock)) {
      $this->languageManagerMock = $this->createLanguageManagerMock();
    }
    if (!isset($this->moduleHandlerMock)) {
      $this->moduleHandlerMock = $this->getMockForAbstractClass(ModuleHandlerInterface::class);
    }
    if (!isset($this->typedDataManagerMock)) {
      $this->typedDataManagerMock = $this->getMockForAbstractClass(TypedDataManagerInterface::class);
    }
    if (!isset($this->cacheBackendMock)) {
      $this->cacheBackendMock = $this->getMockForAbstractClass(CacheBackendInterface::class);
    }

    return new EckEntityTypeBundleInfo($this->entityTypeManagerMock, $this->languageManagerMock, $this->moduleHandlerMock, $this->typedDataManagerMock, $this->cacheBackendMock);
  }

  /**
   * @param PHPUnit_Framework_MockObject_MockObject $entity_type_mock
   * @param PHPUnit_Framework_MockObject_MockObject $entity_storage_mock
   *
   * @return EckEntityTypeBundleInfo
   */
  protected function createNewTestSubjectWithEntityType(PHPUnit_Framework_MockObject_MockObject $entity_type_mock = NULL, PHPUnit_Framework_MockObject_MockObject$entity_storage_mock = NULL) {
    if (!isset($entity_type_mock)) {
      $entity_type_mock = $this->getMockForAbstractClass(EntityTypeInterface::class);
      $entity_type_mock->method('getBundleEntityType')
        ->willReturn('eck_entity_bundle');
    }
    if (!isset($entity_storage_mock)) {
      $entity_storage_mock = $this->getMockForAbstractClass(EntityStorageInterface::class);
      $entity_storage_mock->method('loadMultiple')->willReturn([]);
    }

    $this->entityTypeManagerMock = $this->getMockForAbstractClass(EntityTypeManagerInterface::class);
    $this->entityTypeManagerMock->method('getDefinitions')
      ->willReturn(['existing_entity_type' => $entity_type_mock]);
    $this->entityTypeManagerMock->method('getStorage')
      ->willReturn($entity_storage_mock);

    return $this->createNewTestSubject();
  }

  /**
   * @param int $number_of_bundles
   *
   * @return EckEntityTypeBundleInfo
   */
  protected function createNewTestSubjectWithEntityTypeAndBundles($number_of_bundles = 1) {
    $bundles = [];
    for ($i = 0; $i < $number_of_bundles; $i++) {
      $machine_name = $this->randomMachineName();
      $bundle_mock = $this->getMockForAbstractClass(EntityInterface::class);
      $bundle_mock->method('id')->willReturn(strtolower($machine_name));
      $bundle_mock->method('label')->willReturn($machine_name);
      $bundles[strtolower($machine_name)] = $bundle_mock;
    }
    $entity_storage_mock = $this->getMockForAbstractClass(EntityStorageInterface::class);
    $entity_storage_mock->method('loadMultiple')->willReturn($bundles);
    return $this->createNewTestSubjectWithEntityType(NULL, $entity_storage_mock);
  }

}
