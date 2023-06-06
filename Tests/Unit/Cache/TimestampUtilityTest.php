<?php

namespace JBartels\BeAcl\Tests\Unit\Cache;

use JBartels\BeAcl\Cache\TimestampUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for the timestamp utility.
 */
class TimestampUtilityTest extends UnitTestCase
{
    /**
     * @var TimestampUtility
     */
    protected $timestampUtility;

    /**
     * Initializes the timestamp utility
     */
    public function setUp()
    {
        $this->timestampUtility = $this->getMock(TimestampUtility::class, ['initializeCache']);
        $this->initializeTimestampCache();
    }

    /**
     * @test
     */
    public function newerTimestampThanInCacheIsInvalid()
    {
        $this->timestampUtility->updateTimestamp();
        $isValid = $this->timestampUtility->permissionTimestampIsValid(time() + 100);
        $this->assertTrue($isValid);
    }

    /**
     * @test
     */
    public function olderTimestampThanInCacheIsInvalid()
    {
        $this->timestampUtility->updateTimestamp();
        $isValid = $this->timestampUtility->permissionTimestampIsValid(time() - 100);
        $this->assertFalse($isValid);
    }

    /**
     * Initializes the cache mock in the timestamp utility.
     */
    protected function initializeTimestampCache()
    {
        /** @var FrontendInterface $cacheMock */
        $cacheMock = $this->getMock(FrontendInterface::class, [], [], '', false);
        $this->timestampUtility->setTimestampCache($cacheMock);
    }
}
