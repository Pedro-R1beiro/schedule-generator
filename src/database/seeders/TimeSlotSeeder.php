<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timeSlots = [
            ['name' => '08:00 - 10:00', 'start_time' => '08:00:00', 'end_time' => '10:00:00', 'is_active' => true],
            ['name' => '10:00 - 12:00', 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'is_active' => true],
            ['name' => '14:00 - 16:00', 'start_time' => '14:00:00', 'end_time' => '16:00:00', 'is_active' => true],
            ['name' => '16:00 - 18:00', 'start_time' => '16:00:00', 'end_time' => '18:00:00', 'is_active' => true],
            ['name' => '18:00 - 20:00', 'start_time' => '18:00:00', 'end_time' => '20:00:00', 'is_active' => true],
        ];

        foreach ($timeSlots as $slot) {
            TimeSlot::create($slot);
        }

        $this->command->info('Horários inseridos com sucesso!');
    }
}
