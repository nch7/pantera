<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Redis::flushall();
        Cache::forget('users');
        $this->call(UsersTableSeeder::class);
    }
}
