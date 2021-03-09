<?php
declare(strict_types=1);

namespace Services\Kitchen;

use Services\Appliance;

class Toaster {
	use Appliance;
	use UnimportedSymbol; // this should be a warning
	public function makeToast() {
		$this->energize();
	}
}
