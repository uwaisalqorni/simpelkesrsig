<?php
/**
 * SIMPELKES Automated Test Runner (CLI)
 * 
 * Eksekusi pengujian backend mandiri (Zero-Dependency)
 * Penggunaan:
 *   php tests/run_tests.php             (Jalankan semua test suite)
 *   php tests/run_tests.php AuthTest    (Jalankan test suite tertentu)
 */

define('TEST_START_TIME', microtime(true));

// ANSI Colors untuk CLI
const COLOR_GREEN  = "\033[1;32m";
const COLOR_RED    = "\033[1;31m";
const COLOR_YELLOW = "\033[1;33m";
const COLOR_CYAN   = "\033[1;36m";
const COLOR_BOLD   = "\033[1m";
const COLOR_RESET  = "\033[0m";

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/AuthTest.php';
require_once __DIR__ . '/MultiTenantTest.php';
require_once __DIR__ . '/WorkOrdersTest.php';
require_once __DIR__ . '/TelegramServiceTest.php';
require_once __DIR__ . '/SecurityTest.php';
require_once __DIR__ . '/SettingsTest.php';

$allSuites = [
    'AuthTest',
    'MultiTenantTest',
    'WorkOrdersTest',
    'TelegramServiceTest',
    'SecurityTest',
    'SettingsTest'
];

$filterSuite = $argv[1] ?? null;
$suitesToRun = $allSuites;

if ($filterSuite) {
    $matched = array_filter($allSuites, function($s) use ($filterSuite) {
        return stripos($s, $filterSuite) !== false;
    });
    if (empty($matched)) {
        echo COLOR_RED . "Test suite '{$filterSuite}' tidak ditemukan!\n" . COLOR_RESET;
        echo "Suite tersedia: " . implode(', ', $allSuites) . "\n";
        exit(1);
    }
    $suitesToRun = array_values($matched);
}

echo "\n" . COLOR_BOLD . COLOR_CYAN . "===============================================================\n";
echo "   🏥 SIMPELKES BACKEND AUTOMATED TEST RUNNER (CI/CD)\n";
echo "===============================================================\n" . COLOR_RESET;
echo "Environment: CodeIgniter 3 API & MariaDB Multi-Tenant\n";
echo "Base API   : http://localhost/simpelkesrsig-backend/api\n";
echo "Running    : " . count($suitesToRun) . " Test Suites\n\n";

$totalTests = 0;
$totalAssertions = 0;
$totalPass = 0;
$totalFail = 0;
$allErrors = [];

foreach ($suitesToRun as $suiteClass) {
    echo COLOR_BOLD . "▶ Running {$suiteClass}...\n" . COLOR_RESET;
    $ref = new ReflectionClass($suiteClass);
    $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

    $testMethods = array_filter($methods, function($m) {
        return strpos($m->name, 'test_') === 0;
    });

    foreach ($testMethods as $method) {
        $totalTests++;
        $methodName = $method->name;
        $label = str_replace('test_', '', $methodName);
        $label = str_replace('_', ' ', $label);

        $instance = new $suiteClass();

        try {
            $instance->setUp();
            $instance->$methodName();
            $instance->tearDown();

            $assertions = $instance->getAssertionsCount();
            $totalAssertions += $assertions;
            $totalPass++;

            echo "  " . COLOR_GREEN . "✓ PASS" . COLOR_RESET . " [{$suiteClass}::{$methodName}] ({$assertions} assertions)\n";
        } catch (Throwable $e) {
            $totalFail++;
            try { $instance->tearDown(); } catch (Throwable $ignore) {}

            echo "  " . COLOR_RED . "✗ FAIL" . COLOR_RESET . " [{$suiteClass}::{$methodName}]\n";
            $allErrors[] = [
                'suite'   => $suiteClass,
                'method'  => $methodName,
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine()
            ];
        }
    }
    echo "\n";
}

$elapsed = round(microtime(true) - TEST_START_TIME, 2);

echo COLOR_BOLD . "---------------------------------------------------------------\n" . COLOR_RESET;
echo "TEST RESULTS SUMMARY:\n";
echo "Suites    : " . count($suitesToRun) . "\n";
echo "Total     : {$totalTests} tests\n";
echo "Assertions: {$totalAssertions} assertions verified\n";
echo "Passed    : " . COLOR_GREEN . "{$totalPass} passed" . COLOR_RESET . "\n";

if ($totalFail > 0) {
    echo "Failed    : " . COLOR_RED . "{$totalFail} failed" . COLOR_RESET . "\n";
    echo "Duration  : {$elapsed}s\n\n";

    echo COLOR_RED . COLOR_BOLD . "FAILURES DETAIL:\n" . COLOR_RESET;
    foreach ($allErrors as $idx => $err) {
        $num = $idx + 1;
        echo COLOR_RED . "{$num}) {$err['suite']}::{$err['method']}\n" . COLOR_RESET;
        echo "   Message: {$err['message']}\n";
        echo "   Location: {$err['file']}\n\n";
    }
    exit(1);
} else {
    echo "Failed    : 0 failed\n";
    echo "Duration  : {$elapsed}s\n";
    echo COLOR_BOLD . COLOR_GREEN . "\n🎉 ALL BACKEND UNIT & INTEGRATION TESTS PASSED 100%!\n" . COLOR_RESET;
    echo COLOR_BOLD . COLOR_CYAN . "===============================================================\n\n" . COLOR_RESET;
    exit(0);
}
