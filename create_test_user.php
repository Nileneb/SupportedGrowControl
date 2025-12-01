<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
App\Models\User::updateOrCreate(
 ['email'=>'test@example.com'],
 ['name'=>'Test','password'=>bcrypt('secret')]
);
echo "created test@example.com with password=secret\n";