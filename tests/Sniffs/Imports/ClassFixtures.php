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

A::class;
Z::class;
F::class;
