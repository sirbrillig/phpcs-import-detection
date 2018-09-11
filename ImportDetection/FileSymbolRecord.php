<?php

namespace ImportDetection;

class FileSymbolRecord {
	public $importedFunctions = [];
	public $importedConsts = [];
	public $importedClasses = [];
	public $importedSymbolRecords = [];
	public $seenSymbols = [];

	public function addImportedFunctions($names) {
		$this->importedFunctions = array_merge($this->importedFunctions, $names);
	}

	public function addImportedClasses($names) {
		$this->importedClasses = array_merge($this->importedClasses, $names);
	}

	public function addImportedConsts($names) {
		$this->importedConsts = array_merge($this->importedConsts, $names);
	}

	public function addImportedSymbolRecords($names) {
		$this->importedSymbolRecords = array_merge($this->importedSymbolRecords, $names);
	}
}
