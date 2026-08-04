<?php

namespace Modules\Akademik\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            RefSeeder::class,
            MahasiswaDemoSeeder::class,
            DemoSeeder::class,
            AkademikDemoSeeder::class,
            KrsDemoSeeder::class,
        ]);
    }
}
