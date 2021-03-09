<?php
use NamespaceName\{
	A, // used line 13
	C\D, // not used
	X\Y as Z, // used line 14
};
use NamespaceName\{
	B, // not used
	E\F, // used line 15
	I\J as H, // not used
};
use Some\NS\ {
	ClassName,
	function SubLevel\functionName,
	const Constants\CONSTANT_NAME as SOME_CONSTANT,
	function SubLevel\AnotherName,
	AnotherLevel,
};

A::class;
Z::class;
F::class;

echo ClassName::class;
echo functionName();
echo SOME_CONSTANT;
echo AnotherName();
echo AnotherLevel::class;
