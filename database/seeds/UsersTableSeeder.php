<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Redis;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\User::class, 1000)->create();
    }
}
