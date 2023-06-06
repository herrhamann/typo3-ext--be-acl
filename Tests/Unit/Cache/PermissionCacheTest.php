<?php

namespace JBartels\BeAcl\Tests\Unit\Cache;

use JBartels\BeAcl\Cache\PermissionCache;
use JBartels\BeAcl\Cache\TimestampUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for the permission cache.
 */
class PermissionCacheTest extends UnitTestCase
{
    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var FrontendInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheMock;

    /**
     * @var PermissionCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionCache;

    /**
     * The key used for the test cache entry
     *
     * @var string
     */
    protected $permissionsClauseCacheKey = 'testCachekey';

    /**
     * The value used for the test cache entry
     *
     * @var string
     */
    protected $permissionsClauseCacheValue = 'testCacheValue';

    /**
     * Initializes the permission cache
     */
    public function setUp()
    {
        /** @var BackendUserAuthentication $backendUser */
        $this->backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
    }

    /**
     * @test
     */
    public function flushingCacheInvalidatesPreviouslySetFirstLevelCache()
    {
        $this->initializePermissionCacheMock(['initializeRequiredClasses']);

        /** @var TimestampUtility|\PHPUnit_Framework_MockObject_MockObject $timestampUtility */
        $timestampUtility = $this->getMock(TimestampUtility::class, ['updateTimestamp', 'permissionTimestampIsValid']);
        $timestampUtility->expects($this->once())->method('updateTimestamp');
        $timestampUtility->expects($this->once())->method('permissionTimestampIsValid')->will($this->returnValue(false));
        $this->permissionCache->setTimestampUtility($timestampUtility);

        $this->permissionCache->setPermissionsClause($this->permissionsClauseCacheKey, $this->permissionsClauseCacheValue);
        $this->permissionCache->flushCache();
        $cachedValue = $this->permissionCache->getPermissionsClause($this->permissionsClauseCacheKey);
        $this->assertNull($cachedValue);
    }

    /**
     * @test
     */
    public function flushingCacheInvalidatesPreviouslySetSecondLevelCache()
    {
        $this->initializePermissionCacheMock(['initializeRequiredClasses']);
        $this->permissionCache->disableFirstLevelCache();

        /** @var TimestampUtility|\PHPUnit_Framework_MockObject_MockObject $timestampUtility */
        $timestampUtility = $this->getMock(TimestampUtility::class, ['updateTimestamp', 'permissionTimestampIsValid']);
        $timestampUtility->expects($this->once())->method('updateTimestamp');
        $timestampUtility->expects($this->once())->method('permissionTimestampIsValid')->will($this->returnValue(false));
        $this->permissionCache->setTimestampUtility($timestampUtility);

        $this->permissionCache->setPermissionsClause($this->permissionsClauseCacheKey, $this->permissionsClauseCacheValue);
        $this->permissionCache->flushCache();
        $cachedValue = $this->permissionCache->getPermissionsClause($this->permissionsClauseCacheKey);
        $this->assertNull($cachedValue);
    }

    /**
     * @test
     */
    public function previouslySetCacheValueIsReturnedByFirstLevelCache()
    {
        $this->initializePermissionCacheMock(['initializeRequiredClasses']);
        $this->permissionCache->setPermissionsClause($this->permissionsClauseCacheKey, $this->permissionsClauseCacheValue);
        $cachedValue = $this->permissionCache->getPermissionsClause($this->permissionsClauseCacheKey);
        $this->assertEquals($this->permissionsClauseCacheValue, $cachedValue);
    }

    /**
     * @test
     */
    public function previouslySetCacheValueIsReturnedBySecondLevelCache()
    {
        $this->initializePermissionCacheMock(['initializeRequiredClasses']);

        /** @var TimestampUtility|\PHPUnit_Framework_MockObject_MockObject $timestampUtility */
        $timestampUtility = $this->getMock(TimestampUtility::class, ['permissionTimestampIsValid']);
        $timestampUtility->expects($this->once())->method('permissionTimestampIsValid')->will($this->returnValue(true));
        $this->permissionCache->setTimestampUtility($timestampUtility);

        $this->permissionCache->disableFirstLevelCache();
        $this->permissionCache->setPermissionsClause($this->permissionsClauseCacheKey, $this->permissionsClauseCacheValue);
        $cachedValue = $this->permissionCache->getPermissionsClause($this->permissionsClauseCacheKey);
        $this->assertEquals($this->permissionsClauseCacheValue, $cachedValue);
    }

    /**
     * @param array $mockedMethods
     */
    protected function initializePermissionCacheMock($mockedMethods)
    {
        /** @var PermissionCache $permissionCache */
        $permissionCache = $this->getMock(PermissionCache::class, $mockedMethods);
        $permissionCache->setBackendUser($this->backendUser);

        $cacheBackend = new TransientMemoryBackend('Testing');
        $cacheFrontend = new VariableFrontend('tx_be_acl_permissions', $cacheBackend);

        $permissionCache->setPermissionCache($cacheFrontend);

        $this->permissionCache = $permissionCache;
    }
}
