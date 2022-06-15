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
		// If the symbol's namespace is imported or defined, ignore it
		// If the symbol has no namespace and is itself is imported or defined, ignore it
		if ($this->isSymbolDefined($phpcsFile, $symbol)) {
			$this->debug('found defined symbol: ' . $symbol->getName());
			$this->markSymbolUsed($phpcsFile, $symbol);
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
			return $this->doesSymbolMatchPattern($symbol, "/{$pattern}/");
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
		// If the symbol's namespace is imported, ignore it
		if ($namespace) {
			return $this->isNamespaceImported($phpcsFile, $namespace);
		}
		// If the symbol has no namespace and is itself is imported or defined, ignore it
		return $this->isNamespaceImportedOrDefined($phpcsFile, $symbol);
	}

	private function isNamespaceImported(File $phpcsFile, string $namespace): bool {
		return (
			$this->isClassImported($phpcsFile, $namespace)
			|| $this->isFunctionImported($phpcsFile, $namespace)
			|| $this->isConstImported($phpcsFile, $namespace)
		);
	}

	private function isSymbolAFunctionCall(File $phpcsFile, Symbol $symbol): bool {
		$tokens = $phpcsFile->getTokens();
		$stackPtr = $symbol->getSymbolPosition();
		if (isset($tokens[$stackPtr + 1]) && $tokens[$stackPtr + 1]['type'] === 'T_OPEN_PARENTHESIS') {
			return true;
		}
		return false;
	}

	private function isNamespaceImportedOrDefined(File $phpcsFile, Symbol $symbol): bool {
		$namespace = $symbol->getName();
		$conditions = $symbol->getSymbolConditions();
		return (
			$this->isClassImported($phpcsFile, $namespace)
			|| $this->isClassDefined($phpcsFile, $namespace)
			|| $this->isFunctionImported($phpcsFile, $namespace)
			|| $this->isFunctionDefined($phpcsFile, $symbol, $namespace, $conditions)
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
		$classPtr = $phpcsFile->findNext([T_CLASS, T_INTERFACE, T_TRAIT], 0);
		while ($classPtr) {
			$thisClassName = $phpcsFile->getDeclarationName($classPtr);
			if ($className === $thisClassName) {
				return true;
			}
			$classPtr = $phpcsFile->findNext([T_CLASS, T_INTERFACE, T_TRAIT], $classPtr + 1);
		}
		return false;
	}

	private function isFunctionDefined(File $phpcsFile, Symbol $symbol, string $functionName, array $conditions): bool {
		$tokens = $phpcsFile->getTokens();

		if (! $this->isSymbolAFunctionCall($phpcsFile, $symbol)) {
			return false;
		}

		$scopesToEnter = array_filter(array_keys($conditions), function ($conditionPtr) use ($conditions) {
			return $conditions[$conditionPtr] === T_FUNCTION;
		});
		$this->debug("looking for definition for function {$functionName}");
		$this->debug("my conditions are " . json_encode($conditions));
		$this->debug("scopes to enter " . implode(',', $scopesToEnter));

		// Only look at the inner-most scope and global scope
		$scopesToEnter = [end($scopesToEnter), 0];

		foreach ($scopesToEnter as $scopeStart) {
			$functionToken = $tokens[$scopeStart];
			$scopeEnd = $functionToken['scope_closer'] ?? null;

			// Within each function scope, find all the function definitions and
			// compare their names to the name we are looking for.
			$functionDefinitionsInScope = $this->findAllFunctionDefinitionsInScope($phpcsFile, $scopeStart, $scopeEnd);

			foreach ($functionDefinitionsInScope as $thisFunctionName) {
				$this->debug("is this function the one we want? " . $thisFunctionName);
				if ($functionName === $thisFunctionName) {
					$this->debug("yes indeed");
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Return an array of function names defined in a scope
	 */
	private function findAllFunctionDefinitionsInScope(File $phpcsFile, int $scopeStart, int $scopeEnd = null): array {
		$this->debug("looking for functions defined between {$scopeStart} and {$scopeEnd}");
		$tokens = $phpcsFile->getTokens();
		$functionNames = [];

		$tokensToInvestigate = [T_FUNCTION, T_CLASS, T_TRAIT, T_INTERFACE];

		// Skip the function we are in, but not the global scope
		$functionToken = $tokens[$scopeStart];
		$scopeOffset = $functionToken['type'] === 'T_FUNCTION' ? 2 : 0;
		$functionPtr = $phpcsFile->findNext($tokensToInvestigate, $scopeStart + $scopeOffset, $scopeEnd);

		while ($functionPtr) {
			$functionName = $phpcsFile->getDeclarationName($functionPtr);
			$functionToken = $tokens[$functionPtr];
			$thisFunctionScopeEnd = $functionToken['scope_closer'] ?? 0;

			// Skip things other than IF that have their own scope
			if ($functionToken['type'] !== 'T_FUNCTION') {
				if (! $thisFunctionScopeEnd) {
					$this->debug("function at {$functionPtr} has no end:" . $functionName);
					break;
				}
				$functionPtr = $phpcsFile->findNext($tokensToInvestigate, $thisFunctionScopeEnd, $scopeEnd);
				continue;
			}

			$this->debug("found function at {$functionPtr}:" . $functionName);
			$functionNames[] = $functionName;
			$functionPtr = $phpcsFile->findNext($tokensToInvestigate, $thisFunctionScopeEnd, $scopeEnd);
		}
		return $functionNames;
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
			$namespaceOrAlias = $symbol->getTopLevelNamespace() ?? $symbol->getAlias();
			$this->debug("comparing symbol {$namespaceOrAlias} to alias {$record->getAlias()}");
			if ($record->getAlias() === $namespaceOrAlias) {
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
