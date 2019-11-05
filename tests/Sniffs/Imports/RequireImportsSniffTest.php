<?php
declare(strict_types=1);

namespace ImportDetectionTest;

use PHPUnit\Framework\TestCase;

class RequireImportsSniffTest extends TestCase {
	public function testRequireImportsSniff() {
		$fixtureFile = __DIR__ . '/RequireImportsFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreUnimportedSymbols',
			'/^(something_to_ignore|whitelisted_function|allowed_funcs_\w+)$/'
		);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [
			10,
			15,
			19,
			27,
			30,
			34,
			37,
			39,
			47,
			52,
			57,
			62,
			69,
			71,
			73,
			79,
			87,
			89,
			95,
		];
		$this->assertEquals($expectedLines, $lines);
		$this->assertSame(count($expectedLines), $phpcsFile->getWarningCount());
	}

	public function testRequireImportsSniffFindsUnimportedFunctionsWithNoConfig() {
		$fixtureFile = __DIR__ . '/RequireImportsAllowedPatternFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreUnimportedSymbols',
			''
		);
		$phpcsFile->process();
		$messages = $helper->getWarningMessageRecords($phpcsFile->getWarnings());
		$messages = array_values(array_filter($messages, function ($message) {
			return $message->source === 'ImportDetection.Imports.RequireImports.Symbol';
		}));
		$lines = array_map(function ($message) {
			return $message->rowNumber;
		}, $messages);
		$expectedLines = [
			15,
			16,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffFindsUnusedImportsWithNoConfig() {
		$fixtureFile = __DIR__ . '/RequireImportsAllowedPatternFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreUnimportedSymbols',
			''
		);
		$phpcsFile->process();
		$messages = $helper->getWarningMessageRecords($phpcsFile->getWarnings());
		$messages = array_values(array_filter($messages, function ($message) {
			return $message->source === 'ImportDetection.Imports.RequireImports.Import';
		}));
		$lines = array_map(function ($message) {
			return $message->rowNumber;
		}, $messages);
		$expectedLines = [
			8,
			9,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffIgnoresWhitelistedUnimportedSymbols() {
		$fixtureFile = __DIR__ . '/RequireImportsAllowedPatternFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreUnimportedSymbols',
			'/^(something_to_ignore|whitelisted_function|allowed_funcs_\w+|another_[a-z_]+)$/'
		);
		$phpcsFile->process();
		$messages = $helper->getWarningMessageRecords($phpcsFile->getWarnings());
		$messages = array_values(array_filter($messages, function ($message) {
			return $message->source === 'ImportDetection.Imports.RequireImports.Symbol';
		}));
		$lines = array_map(function ($message) {
			return $message->rowNumber;
		}, $messages);
		$expectedLines = [
			16,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffIgnoresWhitelistedUnusedImports() {
		$fixtureFile = __DIR__ . '/RequireImportsAllowedPatternFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreUnimportedSymbols',
			'/\\\unused_function|\\\four_function/'
		);
		$phpcsFile->process();
		$messages = $helper->getWarningMessageRecords($phpcsFile->getWarnings());
		$messages = array_values(array_filter($messages, function ($message) {
			return $message->source === 'ImportDetection.Imports.RequireImports.Import';
		}));
		$lines = array_map(function ($message) {
			return $message->rowNumber;
		}, $messages);
		$expectedLines = [];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffFindsWordPressPatternsIfNotSet() {
		$fixtureFile = __DIR__ . '/WordPressFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreWordPressSymbols',
			'false'
		);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [
			23,
			32,
			33,
			34,
			35,
			36,
			37,
			38,
			42,
			45,
			48,
			52,
			55,
			60,
			61,
			63,
			74,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffIgnoresWordPressPatternsIfSet() {
		$fixtureFile = __DIR__ . '/WordPressFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreWordPressSymbols',
			'true'
		);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [ 38, 61, 63 ];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffDoesNotCountMethodNames() {
		$fixtureFile = __DIR__ . '/RequireImportsMethodNameFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [ 11 ];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffCountsTraitUseAsUsage() {
		$fixtureFile = __DIR__ . '/UsedTraitFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [10];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffWorksWithInterfaces() {
		$fixtureFile = __DIR__ . '/InterfaceFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [31];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffWorksWithTraits() {
		$fixtureFile = __DIR__ . '/TraitFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [37];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffFindsGlobalSymbolsIfNoConfig() {
		$fixtureFile = __DIR__ . '/GlobalNamespaceFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [
			6,
			7,
			13,
			14,
			19,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffIgnoresGlobalSymbolsIfConfigured() {
		$fixtureFile = __DIR__ . '/GlobalNamespaceFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreGlobalsWhenInGlobalScope',
			'true'
		);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [
			6,
			7,
			19,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffFindsGlobalSymbolsInNamespaceIfConfigured() {
		$fixtureFile = __DIR__ . '/RequireImportsAllowedPatternFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->ruleset->setSniffProperty(
			'ImportDetection\Sniffs\Imports\RequireImportsSniff',
			'ignoreGlobalsWhenInGlobalScope',
			'true'
		);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [
			8,
			9,
			15,
			16,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsDoesNotBleedToMultipleFiles() {
		$fixtureFile = __DIR__ . '/MultipleFilesFixtures';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFiles = $helper->prepareLocalFilesForSniffs($sniffFile, $fixtureFile);
		// Unclear why this works, but if I run this twice the first fixture file
		// gets all its warnings cleared (and the other fixture file has the same
		// warning twice).
		$helper->processFiles($phpcsFiles);
		$helper->processFiles($phpcsFiles);
		$linesByFile = $helper->getNoticesFromFiles($phpcsFiles, 'warning');
		$expectedLines = [
			// The runner runs 'MultipleFilesFixtures2' first.
			__DIR__ . '/MultipleFilesFixtures/MultipleFilesFixtures2.php' => [],
			__DIR__ . '/MultipleFilesFixtures/MultipleFilesFixtures1.php' => [5],
		];
		$this->assertEquals($expectedLines, $linesByFile);
	}

	public function testRequireImportsFindsUnimportedNamespaceIdenticalToClass() {
		$fixtureFile = __DIR__ . '/ClassUsedAsNamespaceFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [
			7,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsNoticesUnusedClasses() {
		$fixtureFile = __DIR__ . '/ClassFixtures.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();

		$warnings = $phpcsFile->getWarnings();
		$messages = $helper->getWarningMessageRecords($warnings);
		$messages = array_values(array_filter($messages, function ($message) {
			return $message->source === 'ImportDetection.Imports.RequireImports.Import';
		}));
		$lines = array_map(function ($message) {
			return $message->rowNumber;
		}, $messages);
		$expectedLines = [
			2,
			7,
			7,
		];
		$this->assertEquals($expectedLines, $lines);
		$this->assertCount(3, $messages);
		$this->assertEquals('Found unused symbol \'NamespaceName\C\D\'.', $messages[0]->message);
		$this->assertEquals('Found unused symbol \'NamespaceName\B\'.', $messages[1]->message);
		$this->assertEquals('Found unused symbol \'NamespaceName\I\J\'.', $messages[2]->message);
	}

	public function testRequireImportsNoticesUnusedConstants() {
		$fixtureFile = __DIR__ . '/ConstantsFixure.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [
			3,
			12,
		];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsSniffTreatsFileImportAsUsedWhenUsed() {
		$fixtureFile = __DIR__ . '/FileKeywordFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [];
		$this->assertEquals($expectedLines, $lines);
	}

	public function testRequireImportsNoticesNestedFunctions() {
		$fixtureFile = __DIR__ . '/NestedFunctionsFixture.php';
		$sniffFile = __DIR__ . '/../../../ImportDetection/Sniffs/Imports/RequireImportsSniff.php';
		$helper = new SniffTestHelper();
		$phpcsFile = $helper->prepareLocalFileForSniffs($sniffFile, $fixtureFile);
		$phpcsFile->process();
		$lines = $helper->getWarningLineNumbersFromFile($phpcsFile);
		$expectedLines = [
			9,
			19,
			47,
			53,
		];
		$this->assertEquals($expectedLines, $lines);
	}
}
