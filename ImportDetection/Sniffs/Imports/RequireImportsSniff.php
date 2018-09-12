<?php

namespace ImportDetection\Sniffs\Imports;

use ImportDetection\Symbol;
use ImportDetection\SniffHelpers;
use ImportDetection\FileSymbolRecord;
use ImportDetection\WordPressSymbols;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class RequireImportsSniff implements Sniff {
	public $ignoreUnimportedSymbols = null;
	public $ignoreGlobalsWhenInGlobalScope = false;
	public $ignoreWordPressSymbols = null;

	private $symbolRecordsByFile = [];

	public function register() {
		return [T_USE, T_STRING, T_RETURN_TYPE, T_WHITESPACE, T_NAMESPACE];
	}

	public function process(File $phpcsFile, $stackPtr) {
		$helper = new SniffHelpers();
		$tokens = $phpcsFile->getTokens();
		$token = $tokens[$stackPtr];
		// Keep one set of symbol records per file
		$this->symbolRecordsByFile[$phpcsFile->path] = $this->symbolRecordsByFile[$phpcsFile->path] ?? new FileSymbolRecord;
		if ($token['type'] === 'T_NAMESPACE') {
			return $this->processNamespace($phpcsFile, $stackPtr);
		}
		if ($token['type'] === 'T_WHITESPACE') {
			$this->debug('found whitespace');
			return $this->processEndOfFile($phpcsFile, $stackPtr);
		}
		if ($token['type'] === 'T_USE') {
			$this->debug('found import');
			if (in_array($helper->getImportType($phpcsFile, $stackPtr), ['function', 'const', 'class'])) {
				$this->recordImportedSymbols($phpcsFile, $stackPtr);
			}
			return $this->processUse($phpcsFile, $stackPtr);
		}
		$symbol = $helper->getFullSymbol($phpcsFile, $stackPtr);
		// If the symbol has been seen before (if this is a duplicate), ignore it
		if (in_array($symbol, $this->symbolRecordsByFile[$phpcsFile->path]->seenSymbols)) {
			$this->debug('found duplicate symbol: ' . $symbol->getName());
			return;
		}
		$this->symbolRecordsByFile[$phpcsFile->path]->seenSymbols[] = $symbol;
		// If the symbol is in the ignore list, ignore it
		if ($this->isSymbolIgnored($symbol)) {
			$this->debug('found ignored symbol: ' . $symbol->getName());
			$this->markSymbolUsed($phpcsFile, $symbol);
			return;
		}
		// If the symbol is a fully-qualified namespace, ignore it
		if ($symbol->isAbsoluteNamespace()) {
			$this->debug('found absolute namespaced symbol: ' . $symbol->getName());
			return;
		}
		// If this symbol is a definition, ignore it
		if ($helper->isSymbolADefinition($phpcsFile, $symbol)) {
			$this->debug('found definition symbol: ' . $symbol->getName());
			return;
		}
		// If this symbol is a static reference or an object reference, ignore it
		if ($helper->isStaticReference($phpcsFile, $stackPtr) || $helper->isObjectReference($phpcsFile, $stackPtr)) {
			$this->debug('found static symbol: ' . $symbol->getName());
			return;
		}
		// If this symbol is a namespace definition, ignore it
		if ($helper->isWithinNamespaceStatement($phpcsFile, $symbol->getSymbolPosition())) {
			$this->debug('found namespace definition symbol: ' . $symbol->getName());
			return;
		}
		// If this symbol is an import, ignore it
		if ($helper->isWithinImportStatement($phpcsFile, $symbol->getSymbolPosition())) {
			$this->debug('found symbol inside an import: ' . $symbol->getName());
			return;
		}
		// If the symbol is predefined, ignore it
		if ($helper->isPredefinedConstant($phpcsFile, $stackPtr) || $helper->isBuiltInFunction($phpcsFile, $stackPtr)) {
			$this->debug('found predefined symbol: ' . $symbol->getName());
			return;
		}
		// If this symbol is a predefined typehint, ignore it
		if ($helper->isPredefinedTypehint($phpcsFile, $stackPtr)) {
			$this->debug('found typehint symbol: ' . $symbol->getName());
			return;
		}
		// If the symbol's namespace is imported or defined, ignore it
		// If the symbol has no namespace and is itself is imported or defined, ignore it
		if ($this->isSymbolDefined($phpcsFile, $symbol)) {
			$this->debug('found defined symbol: ' . $symbol->getName());
			$this->markSymbolUsed($phpcsFile, $symbol);
			return;
		}
		// If the symbol is global, we are in the global namespace, and
		// configured to ignore global symbols in the global namespace,
		// ignore it
		if ($this->ignoreGlobalsWhenInGlobalScope && ! $symbol->isNamespaced() && ! $this->symbolRecordsByFile[$phpcsFile->path]->activeNamespace) {
			$this->debug('found global symbol in global namespace: ' . $symbol->getName());
			return;
		}
		$this->debug('found unimported symbol: ' . $symbol->getName());
		$error = "Found unimported symbol '{$symbol->getName()}'.";
		$phpcsFile->addWarning($error, $stackPtr, 'Symbol');
	}

	private function debug(string $message) {
		if (! defined('PHP_CODESNIFFER_VERBOSITY')) {
			return;
		}
		if (PHP_CODESNIFFER_VERBOSITY > 3) {
			echo PHP_EOL . "RequireImportsSniff: DEBUG: $message" . PHP_EOL;
		}
	}

	private function isSymbolIgnored(Symbol $symbol): bool {
		$ignorePattern = $this->getIgnoredSymbolPattern();
		$doesSymbolMatchIgnorePattern = $this->doesSymbolMatchPattern($symbol, $ignorePattern);
		if ($doesSymbolMatchIgnorePattern) {
			return true;
		}

		$wordPressPatterns = $this->getIgnoredWordPressSymbolPatterns();
		$matchingWordPressPatterns = array_values(array_filter($wordPressPatterns, function (string $pattern) use ($symbol): bool {
			return $this->doesSymbolMatchPattern($symbol, "/${pattern}/");
		}));
		return count($matchingWordPressPatterns) > 0;
	}

	private function doesSymbolMatchPattern(Symbol $symbol, string $pattern): bool {
		$symbolName = $symbol->getName();
		if (empty($pattern)) {
			return false;
		}
		try {
			return (1 === preg_match($pattern, $symbolName));
		} catch (\Exception $err) {
			throw new \Exception("Invalid ignore pattern found: '{$pattern}'");
		}
	}

	private function getIgnoredWordPressSymbolPatterns() {
		return empty($this->ignoreWordPressSymbols) ? [] : WordPressSymbols::getWordPressSymbolPatterns();
	}

	private function getIgnoredSymbolPattern() {
		return $this->ignoreUnimportedSymbols ?? '';
	}

	private function isSymbolDefined(File $phpcsFile, Symbol $symbol): bool {
		$namespace = $symbol->getTopLevelNamespace();
		// If the symbol's namespace is imported or defined, ignore it
		if ($namespace) {
			return $this->isNamespaceImportedOrDefined($phpcsFile, $namespace);
		}
		// If the symbol has no namespace and is itself is imported or defined, ignore it
		return $this->isNamespaceImportedOrDefined($phpcsFile, $symbol->getName());
	}

	private function isNamespaceImportedOrDefined(File $phpcsFile, string $namespace): bool {
		return (
			$this->isClassImported($phpcsFile, $namespace)
			|| $this->isClassDefined($phpcsFile, $namespace)
			|| $this->isFunctionImported($phpcsFile, $namespace)
			|| $this->isFunctionDefined($phpcsFile, $namespace)
			|| $this->isConstImported($phpcsFile, $namespace)
			|| $this->isConstDefined($phpcsFile, $namespace)
		);
	}

	private function processUse(File $phpcsFile, $stackPtr) {
		$helper = new SniffHelpers();
		$importType = $helper->getImportType($phpcsFile, $stackPtr);
		switch ($importType) {
			case 'function':
				return $this->saveFunctionImport($phpcsFile, $stackPtr);
			case 'const':
				return $this->saveConstImport($phpcsFile, $stackPtr);
			case 'class':
				return $this->saveClassImport($phpcsFile, $stackPtr);
		}
	}

	private function recordImportedSymbols(File $phpcsFile, int $stackPtr) {
		$helper = new SniffHelpers();
		$symbols = $helper->getImportedSymbolsFromImportStatement($phpcsFile, $stackPtr);
		$this->debug('recording imported symbols: ' . implode(', ', array_map(function (Symbol $symbol): string {
			return $symbol->getName();
		}, $symbols)));
		$symbols = array_map(function ($symbol) {
			if ($this->isSymbolIgnored($symbol)) {
				$this->debug('found ignored imported symbol: ' . $symbol->getName());
				$symbol->markUsed();
			}
			return $symbol;
		}, $symbols);
		$this->symbolRecordsByFile[$phpcsFile->path]->addImportedSymbolRecords($symbols);
	}

	private function saveFunctionImport(File $phpcsFile, $stackPtr) {
		$helper = new SniffHelpers();
		$importNames = $helper->getImportNames($phpcsFile, $stackPtr);
		$this->symbolRecordsByFile[$phpcsFile->path]->addImportedFunctions($importNames);
	}

	private function saveConstImport(File $phpcsFile, $stackPtr) {
		$helper = new SniffHelpers();
		$importNames = $helper->getImportNames($phpcsFile, $stackPtr);
		$this->symbolRecordsByFile[$phpcsFile->path]->addImportedConsts($importNames);
	}

	private function saveClassImport(File $phpcsFile, $stackPtr) {
		$helper = new SniffHelpers();
		$importNames = $helper->getImportNames($phpcsFile, $stackPtr);
		$this->symbolRecordsByFile[$phpcsFile->path]->addImportedClasses($importNames);
	}

	private function isFunctionImported(File $phpcsFile, string $functionName): bool {
		return in_array($functionName, $this->symbolRecordsByFile[$phpcsFile->path]->importedFunctions);
	}

	private function isConstImported(File $phpcsFile, string $constName): bool {
		return in_array($constName, $this->symbolRecordsByFile[$phpcsFile->path]->importedConsts);
	}

	private function isClassImported(File $phpcsFile, string $name): bool {
		return in_array($name, $this->symbolRecordsByFile[$phpcsFile->path]->importedClasses);
	}

	private function isClassDefined(File $phpcsFile, string $className): bool {
		$classPtr = $phpcsFile->findNext([T_CLASS, T_INTERFACE], 0);
		while ($classPtr) {
			$thisClassName = $phpcsFile->getDeclarationName($classPtr);
			if ($className === $thisClassName) {
				return true;
			}
			$classPtr = $phpcsFile->findNext([T_CLASS, T_INTERFACE], $classPtr + 1);
		}
		return false;
	}

	private function isFunctionDefined(File $phpcsFile, string $functionName): bool {
		$helper = new SniffHelpers();
		$functionPtr = $phpcsFile->findNext([T_FUNCTION], 0);
		while ($functionPtr) {
			$thisFunctionName = $phpcsFile->getDeclarationName($functionPtr);
			if ($functionName === $thisFunctionName && ! $helper->isFunctionAMethod($phpcsFile, $functionPtr)) {
				return true;
			}
			$functionPtr = $phpcsFile->findNext([T_FUNCTION], $functionPtr + 1);
		}
		return false;
	}

	private function isConstDefined(File $phpcsFile, string $functionName): bool {
		$helper = new SniffHelpers();
		$functionPtr = $phpcsFile->findNext([T_CONST], 0);
		while ($functionPtr) {
			$thisFunctionName = $helper->getConstantName($phpcsFile, $functionPtr);
			if ($functionName === $thisFunctionName) {
				return true;
			}
			$functionPtr = $phpcsFile->findNext([T_CONST], $functionPtr + 1);
		}
		return false;
	}

	private function markSymbolUsed(File $phpcsFile, Symbol $symbol) {
		$record = $this->getRecordedImportedSymbolMatchingSymbol($phpcsFile, $symbol);
		if (! $record) {
			// Symbol records only exist for imported symbols, so if a used symbol
			// has not been imported we don't need to mark anything.
			$this->debug("ignoring marking symbol used since it was never imported: {$symbol->getName()}");
			return;
		}
		$record->markUsed();
	}

	private function getRecordedImportedSymbolMatchingSymbol(File $phpcsFile, Symbol $symbol) {
		foreach ($this->symbolRecordsByFile[$phpcsFile->path]->importedSymbolRecords as $record) {
			$this->debug("comparing symbol {$symbol->getTopLevelNamespace()} to alias {$record->getAlias()}");
			if ($record->getAlias() === $symbol->getTopLevelNamespace()) {
				return $record;
			}
		}
		return null;
	}

	private function processEndOfFile(File $phpcsFile, int $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		// If this is not the end of the file, ignore it
		if (isset($tokens[$stackPtr + 1])) {
			return;
		}
		// For each import, if the Symbol was not used, mark a warning
		foreach ($this->symbolRecordsByFile[$phpcsFile->path]->importedSymbolRecords as $record) {
			if (! $record->isUsed()) {
				$this->debug("found unused symbol: {$record->getName()}");
				$error = "Found unused symbol '{$record->getName()}'.";
				$phpcsFile->addWarning($error, $record->getSymbolPosition(), 'Import');
			}
		}
	}

	private function processNamespace(File $phpcsFile, int $stackPtr) {
		$helper = new SniffHelpers();
		$symbols = $helper->getImportedSymbolsFromImportStatement($phpcsFile, $stackPtr);
		if (count($symbols) < 1) {
			return;
		}
		if (count($symbols) > 1) {
			throw new \Exception('Found more than one namespace: ' . var_export($symbols, true));
		}
		$this->debug('we are in the namespace: ' . $symbols[0]->getName());
		$this->symbolRecordsByFile[$phpcsFile->path]->activeNamespace = $symbols[0];
	}
}
