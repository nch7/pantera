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
        factory(App\User::class, 500)->create();
        App\User::create([
            "name" => "Nick Chikovani",
            "role" => "Developer",
            "team" => "A1",
            "password" => bcrypt("123123"),
            "email" => "nickeof@gmail.com"
        ]);
    }
}
