<?php

//TODO: DEPRICATED
function array_keyding($array, $column)
{
	return array_combine( array_column((array) $array, $column), (array) $array );
}

function array_keycolumn($column, $array)
{
	return array_combine( array_column((array) $array, $column), (array) $array );
}

function array_key_column($column, $array)
{
	return array_combine( array_column((array) $array, $column), (array) $array );
}
