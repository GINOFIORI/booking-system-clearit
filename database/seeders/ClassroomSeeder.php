<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        $classrooms = [];
        for ($i = 0; $i < 10; $i++) {
            $classrooms[] = [
                'name' => 'Classroom ' . ($i + 1),
                'days' => implode(', ', $faker->randomElements(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], rand(1, 5))),
                'start_time' => $faker->time('H:i:s'),
                'end_time' => $faker->time('H:i:s', '+2 hours'),
                'capacity' => rand(20, 50),
                'interval_minutes' => rand(30, 60),
            ];
        }

        DB::table('classrooms')->insert($classrooms);
    }
}
