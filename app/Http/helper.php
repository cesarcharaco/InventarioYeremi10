<?php 
function locales()
{
	$locales=App\Models\Local::all();

	return $locales;
}