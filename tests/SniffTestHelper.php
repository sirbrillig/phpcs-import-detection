<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
declare(strict_types=1);

namespace ImportDetectionTest;

use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Files\FileList;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;

class MessageRecord {
	public $rowNumber;
	public $columnNumber;
	public $message;
	public $source;
}

class SniffTestHelper {
	public function prepareLocalFileForSniffs($sniffFiles, string $fixtureFile): LocalFile {
		$config = new Config();
		$ruleset = new Ruleset($config);
		if (! is_array($sniffFiles)) {
			$sniffFiles = [$sniffFiles];
		}
		$ruleset->registerSniffs($sniffFiles, [], []);
		$ruleset->populateTokenListeners();
		if (! file_exists($fixtureFile)) {
			throw new \Exception('Fixture file does not exist! ' . $fixtureFile);
		}
		return new LocalFile($fixtureFile, $ruleset, $config);
	}

	public function prepareLocalFilesForSniffs($sniffFiles, $fixtureFiles): FileList {
		$config = new Config();
		if (! is_array($fixtureFiles)) {
			$fixtureFiles = [$fixtureFiles];
		}
		$config->files = $fixtureFiles;
		$ruleset = new Ruleset($config);
		if (! is_array($sniffFiles)) {
			$sniffFiles = [$sniffFiles];
		}
		$ruleset->registerSniffs($sniffFiles, [], []);
		$ruleset->populateTokenListeners();
		return new FileList($config, $ruleset);
	}

	public function processFiles($sniffFiles) {
		foreach ($sniffFiles as $phpcsFile) {
			if (! file_exists($phpcsFile->path)) {
				throw new \Exception('Fixture file does not exist! ' . $phpcsFile->path);
			}
			$phpcsFile->process();
		}
	}

	public function getWarningMessageRecords(array $messages) {
		$messageRecords = [];
		foreach ($messages as $rowNumber => $messageRow) {
			foreach ($messageRow as $columnNumber => $messageArrays) {
				foreach ($messageArrays as $messageArray) {
					$messageRecord = new MessageRecord();
					$messageRecord->rowNumber = $rowNumber;
					$messageRecord->columnNumber = $columnNumber;
					$messageRecord->message = $messageArray['message'];
					$messageRecord->source = $messageArray['source'];
					$messageRecords[] = $messageRecord;
				}
			}
		}
		return $messageRecords;
	}

	public function getLineNumbersFromMessages(array $messages): array {
		$lines = array_keys($messages);
		sort($lines);
		return $lines;
	}

	public function getNoticesFromFiles($phpcsFiles, string $noticeType): array {
		$noticesByFile = [];
		foreach ($phpcsFiles as $phpcsFile) {
			switch ($noticeType) {
				case 'warning':
					$noticesByFile[$phpcsFile->path] = $this->getLineNumbersFromMessages($phpcsFile->getWarnings());
					break;
				case 'error':
					$noticesByFile[$phpcsFile->path] = $this->getLineNumbersFromMessages($phpcsFile->getErrors());
					break;
				default:
					throw new \Exception("Invalid notice type '{$noticeType}'");
			}
		}
		return $noticesByFile;
	}

	public function getWarningLineNumbersFromFile(LocalFile $phpcsFile): array {
		return $this->getLineNumbersFromMessages($phpcsFile->getWarnings());
	}

	public function getErrorLineNumbersFromFile(LocalFile $phpcsFile): array {
		return $this->getLineNumbersFromMessages($phpcsFile->getErrors());
	}

	public function getFixedFileContents(LocalFile $phpcsFile) {
		$phpcsFile->fixer->startFile($phpcsFile);
		$phpcsFile->fixer->fixFile();
		return $phpcsFile->fixer->getContents();
	}
}
