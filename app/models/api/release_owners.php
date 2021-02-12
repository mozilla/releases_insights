<?php

$data = include DATA .'release_owners.php';

// Reconstruct the array to have integers as version numbers in keys
return array_combine(
    array_map('intval', array_keys($data)),
    array_values($data)
);
