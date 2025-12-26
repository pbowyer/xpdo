<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test;

use ArrayObject;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use xPDO\TestCase;
use xPDO\xPDO;
use xPDO\xPDOLogger;

class LoggerTest extends TestCase
{
    public function testLoggerDebugStateIsSynced()
    {
        $logger = $this->xpdo->getLogger();
        if (!$logger instanceof xPDOLogger) {
            $this->markTestSkipped('xPDOLogger is not installed for this fixture.');
        }

        $this->xpdo->setDebug(true);
        $this->assertTrue($logger->getDebug());
        $this->xpdo->setDebug(false);
        $this->assertFalse($logger->getDebug());
    }

    public function testLegacyLogLevelsAreTranslatedForCustomLogger()
    {
        $logger = new CapturingLogger();
        $this->xpdo->setLogger($logger);

        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Test error');
        $this->assertSame(LogLevel::ERROR, $logger->lastLevel);

        $this->xpdo->log(xPDO::LOG_LEVEL_FATAL, 'Test fatal');
        $this->assertSame(LogLevel::EMERGENCY, $logger->lastLevel);
    }

    public function testArrayAccessTargetsCaptureLogs()
    {
        $cacheManager = $this->xpdo->getCacheManager();

        $arrayTarget = new ArrayObject();
        $arrayLogger = new xPDOLogger(
            $cacheManager,
            ['target' => 'ARRAY', 'options' => ['var' => $arrayTarget]],
            LogLevel::DEBUG
        );
        $arrayLogger->info('Array target');
        $this->assertCount(1, $arrayTarget);

        $extendedTarget = new ArrayObject();
        $extendedLogger = new xPDOLogger(
            $cacheManager,
            ['target' => 'ARRAY_EXTENDED', 'options' => ['var' => $extendedTarget]],
            LogLevel::DEBUG
        );
        $extendedLogger->info('Extended target', [
            'def' => 'LoggerTest',
            'file' => 'logger-test.php',
            'line' => 123,
            'extra' => 'value',
        ]);

        $this->assertCount(1, $extendedTarget);
        $entry = $extendedTarget[0];
        $this->assertSame(' in LoggerTest', $entry['def']);
        $this->assertSame(' @ logger-test.php', $entry['file']);
        $this->assertSame(' : 123', $entry['line']);
        $this->assertSame('value', $entry['extra']);
    }

    public function testGetLogLevelReturnsConfiguredValue()
    {
        $cacheManager = $this->xpdo->getCacheManager();
        $logger = new xPDOLogger($cacheManager, 'ECHO', xPDO::LOG_LEVEL_WARN);
        $this->assertSame(LogLevel::WARNING, $logger->getLogLevel());
    }
}

class CapturingLogger implements LoggerInterface
{
    public $lastLevel;
    public $lastMessage;
    public $lastContext = [];

    public function log($level, $message, array $context = []): void
    {
        $this->lastLevel = $level;
        $this->lastMessage = $message;
        $this->lastContext = $context;
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
