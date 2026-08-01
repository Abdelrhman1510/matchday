<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = new \Illuminate\Http\Request();
$request->merge([
    'home_team_id' => 1,
    'away_team_id' => 2,
    'match_date' => '2026-08-01',
    'booking_opens_at' => '2026-08-01 12:00:00',
    'booking_closes_at' => '2026-08-01 12:00:00',
]);

$validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
    'home_team_id' => 'required|integer',
    'away_team_id' => 'required|integer',
    'match_date' => 'required|date|after_or_equal:today',
    'booking_opens_at' => 'sometimes|nullable|date',
    'booking_closes_at' => 'sometimes|nullable|date|after:booking_opens_at',
]);

dump($validator->fails());
dump($validator->errors()->toArray());
