<?php

namespace Database\Seeders;

use App\Models\WeekdayTimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WeekdayTimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapeamento: weekday_id => [time_slot_id1, time_slot_id2, ...]
        // Supondo que os IDs sejam:
        // Monday=1, Tuesday=2, Wednesday=3, Thursday=4, Friday=5, Saturday=6, Sunday=7
        // 08:00-10:00=1, 10:00-12:00=2, 14:00-16:00=3, 16:00-18:00=4, 18:00-20:00=5
        
        $schedule = [
            1 => [1, 2, 3, 4, 5], // Segunda: todos os horários
            2 => [1, 2, 3, 4, 5], // Terça: todos os horários
            3 => [1, 2, 5],       // Quarta: 8-10, 10-12, 18-20
            4 => [3, 4],          // Quinta: 14-16, 16-18
            5 => [1, 2],          // Sexta: 8-10, 10-12
            6 => [3, 4, 5],       // Sábado: 14-16, 16-18, 18-20
            7 => [],              // Domingo: sem horários
        ];

        $totalCreated = 0;

        foreach ($schedule as $weekdayId => $timeSlotIds) {
            foreach ($timeSlotIds as $timeSlotId) {
                // Verificar se já existe
                $exists = WeekdayTimeSlot::where('weekday_id', $weekdayId)
                    ->where('time_slot_id', $timeSlotId)
                    ->exists();

                if (!$exists) {
                    WeekdayTimeSlot::create([
                        'weekday_id' => $weekdayId,
                        'time_slot_id' => $timeSlotId
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->command->info("{$totalCreated} relacionamentos criados com sucesso!");
    }
}
