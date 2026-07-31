<?php

return [
    'menu' => "Welcome to Build With Abdallah Support.\n\nPlease select the product you need help with:\n\n1 — Kirada\n2 — Djib Payroll\n3 — SMKit\n4 — Custom software\n5 — General support",
    'menu_commands' => ['menu', 'main menu', 'change product', 'switch product', 'start over', '0'],
    'products' => [
        'kirada' => ['selection' => '1', 'aliases' => ['kirada'], 'application_slug' => 'kirada', 'confirmation' => 'You are now connected to Kirada support.'],
        'djib-payroll' => ['selection' => '2', 'aliases' => ['djib payroll', 'djib-payroll'], 'application_slug' => 'djib-payroll', 'confirmation' => 'You are now connected to Djib Payroll support.'],
        'smkit' => ['selection' => '3', 'aliases' => ['smkit'], 'application_slug' => 'smkit', 'confirmation' => 'You are now connected to SMKit support.'],
        'custom-software' => ['selection' => '4', 'aliases' => ['custom software'], 'application_slug' => null, 'confirmation' => 'You are now connected to custom software support.'],
        'general-support' => ['selection' => '5', 'aliases' => ['support', 'general support'], 'application_slug' => null, 'confirmation' => 'You are now connected to Build With Abdallah support.'],
    ],
];
