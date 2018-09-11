<?php
declare(strict_types=1);

namespace ImportDetection;

class Symbol {
	private $tokens;

	public function __construct(array $tokens) {
		if (empty($tokens)) {
			throw new \Exception('Cannot construct Symbol with no tokens');
		}
		foreach ($tokens as $token) {
			if (empty($token) || ! is_array($token)) {
				throw new \Exception('Cannot construct Symbol with invalid token: ' . var_export($token, true));
			}
		}
		$this->tokens = $tokens;
	}

	public static function getTokenWithPosition(array $token, int $stackPtr): array {
		$token['tokenPosition'] = $stackPtr;
		return $token;
	}

	public function getTokens(): array {
		return $this->tokens;
	}

	public function getName(): string {
		return $this->joinSymbolParts($this->tokens);
	}

	public function getAlias(): string {
		return $this->tokens[count($this->tokens) - 1]['content'];
	}

	public function isAbsoluteNamespace(): bool {
		$type = $this->tokens[0]['type'] ?? '';
		return $type === 'T_NS_SEPARATOR';
	}

	/**
	 * @return string|null
	 */
	public function getTopLevelNamespace() {
		return $this->tokens[0]['content'] ?? null;
	}

	public function getSymbolPosition(): int {
		return $this->tokens[0]['tokenPosition'] ?? 1;
	}

	private function joinSymbolParts(array $tokens): string {
		$symbolStrings = array_map(function (array $token): string {
			return $token['content'] ?? '';
		}, $tokens);
		return implode('', $symbolStrings);
	}
}
