<?php
declare(strict_types=1);

namespace Services\Kitchen;

use Services\Appliance;

class Toaster {
	use Appliance;
	public function makeToast() {
		$this->energize();
	}
}
