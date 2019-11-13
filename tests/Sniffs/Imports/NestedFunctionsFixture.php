<?php
function doThing() {

	function nestedFuncA( $arg ) {
		echo $arg . ' world';
	}

	nestedFuncA( 'hello' );
	nestedFuncB( 'hello' ); // warning: undefined
}

class MyThing() {
	public function doAThing() {
		function nestedFuncB( $arg ) {
			echo $arg . ' world';
		}

		nestedFuncB( 'hello' );
		nestedFuncA( 'hello' ); // warning: undefined
		$this->nestedFuncA( 'hello' );

		if (true) {
			nestedFuncB( 'boo' );
		}

		if (true) {
			function nestedFuncC() {
				echo 'we are deep now';
			}

			nestedFuncC();
		}
	}

	public function doANestedThing() {
		function nestedFuncA() {
			echo 'nope';
		}

		\registerThing(new class {
			public function subClassFunc() {
				function nestedFuncC( $arg ) {
					echo $arg . ' world';
				}

				nestedFuncC( 'hello' );
				nestedFuncA( 'blarg' ); // warning: undefined
			}
		});
	}
}

nestedFuncA( 'hi' ); // warning: undefined
