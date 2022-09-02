<?php

function array_keyding($array, $column)
{
	return array_combine( array_column((array) $array, $column), $array );
}
