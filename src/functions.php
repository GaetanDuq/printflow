<?php

function formatJPY($amount) {
    return '¥' . number_format($amount);
}

function estimatePrice($material, $weightGrams)
{
  $base = 500;
  $perGram = 8;
  $multipliers = [
    "PLA" => 1.0,
    "ABS" => 1.2,
    "Aluminum" => 2.0,
    "Titanium" => 3.0
  ];

  //checking if the material exists in the multipliers array
  if (!isset($multipliers[$material]))
  {
    return null;
  }

  return $base + ($perGram * $weightGrams * $multipliers[$material]);
}
