<?php
// Davidsons UI additions - to be manually inserted

// 1. ADD TO all_distributors array (line 200):
// Change: $all_distributors = array('lipseys', 'rsr_group', 'orion');
// To:     $all_distributors = array('lipseys', 'rsr_group', 'orion', 'davidsons');

// 2. ADD TO load_distributors function (after line 726):
// Add:    'davidsons' => array('name' => 'Davidsons', 'status' => 'ready'),

// 3. ADD TO get_distributor_display_name function:
// Add:    'davidsons' => 'Davidsons',

// 4. ADD TO settings page (after RSR settings section):
// Add Davidsons configuration section with markup setting
