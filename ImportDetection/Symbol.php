<?php
declare(strict_types=1);

namespace ImportDetection;

class Symbol {
	private $tokens;
	private $isUsed;
	private $alias;

	public function __construct(array $tokens, self $alias = null) {
		if (empty($tokens)) {
			throw new \Exception('Cannot construct Symbol with no tokens');
		}
		foreach ($tokens as $token) {
			if (empty($token) || ! is_array($token)) {
				throw new \Exception('Cannot construct Symbol with invalid token: ' . var_export($token, true));
			}
		}
		$this->tokens = $tokens;
		$this->isUsed = false;
		$this->alias = $alias;
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
		if ($this->alias) {
			return $this->alias->getName();
		}
		if (count($this->tokens) === 1 && $this->tokens[0]['type'] === 'T_NAME_QUALIFIED') {
			$parts = explode('\\', $this->tokens[0]['content']);
			return $parts[count($parts) - 1];
		}

		return $this->tokens[count($this->tokens) - 1]['content'];
	}

	public function isAbsoluteNamespace(): bool {
		$type = $this->tokens[0]['type'] ?? '';
		return $type === 'T_NS_SEPARATOR';
	}

	public function isNamespaced(): bool {
		return count($this->tokens) > 1;
	}

	/**
	 * @return string|null
	 */
	public function getTopLevelNamespace() {
		if (! $this->isNamespaced()) {
			return null;
		}
		return $this->tokens[0]['content'] ?? null;
	}

	public function getSymbolPosition(): int {
		return $this->tokens[0]['tokenPosition'] ?? 1;
	}

	public function getSymbolConditions(): array {
		return $this->tokens[0]['conditions'] ?? [];
	}

	public function markUsed() {
		$this->isUsed = true;
	}

	public function isUsed(): bool {
		return $this->isUsed;
	}

	private function joinSymbolParts(array $tokens): string {
		$symbolStrings = array_map(function (array $token): string {
			return $token['content'] ?? '';
		}, $tokens);
		return implode('', $symbolStrings);
	}
}
