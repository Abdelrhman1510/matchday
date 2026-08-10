<?php require "vendor/autoload.php"; $app = require_once "bootstrap/app.php"; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); 
$rand = rand(1000, 9999);
$email = "testqa" . $rand . "@example.com";
$phone = "123456" . $rand;
// 1. Create a cafe owner
$user = App\Models\User::create(["name" => "QA", "email" => $email, "phone" => $phone, "password" => "secret", "role" => "cafe_owner"]);
$cafe = App\Models\Cafe::create(["owner_id" => $user->id, "name" => "QA Cafe", "email" => $email, "phone" => $phone, "status" => "active", "city" => "Riyadh"]);
$branch = App\Models\Branch::create(["cafe_id" => $cafe->id, "name" => "Main", "address" => "123 Street", "city" => "Riyadh", "phone" => $phone, "latitude" => 0, "longitude" => 0, "total_seats" => 50]);
echo "Created User ID: " . $user->id . "\n";
echo "Created Cafe ID: " . $cafe->id . "\n";
// 2. Delete the user
app(App\Services\ProfileService::class)->deleteAccount($user);
echo "User deleted.\n";
// 3. Re-register using AuthService::completeRegistration (simulating the flutter app)
Illuminate\Support\Facades\Cache::put("reg_verified:{$email}", true, now()->addMinutes(30));
$newData = ["name" => "QA New", "email" => $email, "phone" => $phone, "password" => "secret"];
$newReg = app(App\Services\AuthService::class)->completeRegistration($newData, "cafe_owner");
$newUser = $newReg["user"];
echo "New User ID: " . $newUser->id . "\n";
$newCafe = $newUser->ownedCafes()->first();
echo "New User Cafe: " . ($newCafe ? $newCafe->id : "None") . "\n";
