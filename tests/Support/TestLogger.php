<?php

/**
 * Test Logger for FOfX Guzzle Middleware
 *
 * This file contains the TestLogger class, a PSR-3 compliant logger implementation
 * designed specifically for testing purposes within the FOfX Guzzle Middleware package.
 * It provides functionality to capture log messages in memory, allowing for easy
 * inspection and assertion in test scenarios.
 *
 * Key features:
 * - Implements PSR-3 LoggerInterface for compatibility with the middleware
 * - Stores log messages in memory for later retrieval and analysis
 * - Provides methods to check for specific log messages and clear log history
 * - Useful for verifying logging behavior in unit and integration tests
 *
 * This logger is not intended for production use, but rather as a tool for
 * developers to ensure proper logging functionality in their tests.
 *
 * @package  FOfX\GuzzleMiddleware\Tests\Support
 */

namespace FOfX\GuzzleMiddleware\Tests\Support;

use Psr\Log\LoggerInterface;

/**
 * TestLogger class for capturing and analyzing log messages in tests.
 *
 * Implements PSR-3 LoggerInterface and provides methods to inspect logged messages.
 */
class TestLogger implements LoggerInterface
{
    public array $logs = [];

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed   $level    The log level.
     * @param  string  $message  The log message.
     * @param  array   $context  The context array.
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message instanceof \Stringable ? $message->__toString() : $message,
            'context' => $context
        ];
    }

    /**
     * Check if a specific message has been logged.
     *
     * @param   string  $message  The message to look for.
     * @return  bool              True if the message is found, otherwise false.
     */
    public function hasLog(string $message): bool
    {
        foreach ($this->logs as $log) {
            if (strpos($log['message'], $message) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all the logged messages.
     *
     * @return  array    The array of logged messages.
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    public function clearLogs(): void
    {
        $this->logs = [];
    }
}
