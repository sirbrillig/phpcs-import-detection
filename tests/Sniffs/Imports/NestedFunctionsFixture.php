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
	}
}
