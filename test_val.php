<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = new \App\Http\Requests\StorePaymentMethodRequest();
$validator = \Illuminate\Support\Facades\Validator::make(
    ['type' => 'credit_card', 'card_number' => '12345678901234', 'card_holder' => '&^%%...', 'expiry_month' => '12', 'expiry_year' => '2028'],
    $request->rules(),
    $request->messages()
);
dump($validator->errors()->toArray());
