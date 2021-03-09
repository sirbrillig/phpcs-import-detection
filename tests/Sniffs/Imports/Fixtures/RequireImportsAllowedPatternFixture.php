<?php
declare(strict_types=1);

namespace CAR_APP\Vehicles\Greatness;

use MagicCode\Snacks;
use function CAR_APP\Functions\whitelisted_function;
use function CAR_APP\Functions\unused_function; // unused
use function CAR_APP\Functions\{ three_function, four_function }; // unused
use function Other_App\{ one_function, two_function };

class GreatClass {
	private function activate() {
		whitelisted_function();
		another_whitelisted_function(); // unimported
		non_whitelisted_function(); // unimported
		one_function();
		two_function();
		three_function();
		Snacks\do_that_magic();
	}
}
