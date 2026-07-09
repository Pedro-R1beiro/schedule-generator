<?php

namespace Database\Seeders;

use App\Models\Weekday;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class WeekdaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Detectar o idioma atual da aplicação
        $locale = App::getLocale();
        
        // Definir os dias da semana com base no idioma
        $weekdays = $this->getWeekdaysByLocale($locale);

        foreach ($weekdays as $weekday) {
            Weekday::create($weekday);
        }

        $this->command->info("Dias da semana inseridos com sucesso! (Idioma: {$locale})");
    }

    /**
     * Retorna os dias da semana no idioma especificado
     */
    private function getWeekdaysByLocale(string $locale): array
    {
        // Dias da semana em português
        $portuguese = [
            ['name' => 'Segunda-feira', 'display_order' => 1],
            ['name' => 'Terca-feira', 'display_order' => 2],
            ['name' => 'Quarta-feira', 'display_order' => 3],
            ['name' => 'Quinta-feira', 'display_order' => 4],
            ['name' => 'Sexta-feira', 'display_order' => 5],
            ['name' => 'Sabado', 'display_order' => 6],
            ['name' => 'Domingo', 'display_order' => 7],
        ];

        // Dias da semana em inglês (padrão)
        $english = [
            ['name' => 'Monday', 'display_order' => 1],
            ['name' => 'Tuesday', 'display_order' => 2],
            ['name' => 'Wednesday', 'display_order' => 3],
            ['name' => 'Thursday', 'display_order' => 4],
            ['name' => 'Friday', 'display_order' => 5],
            ['name' => 'Saturday', 'display_order' => 6],
            ['name' => 'Sunday', 'display_order' => 7],
        ];

        // Mapear idiomas para seus respectivos arrays
        $locales = [
            'pt' => $portuguese,
            'pt_BR' => $portuguese,
            'pt_PT' => $portuguese,
            'en' => $english,
            'en_US' => $english,
            'en_GB' => $english,
        ];

        // Retornar o array correspondente ao idioma ou inglês como fallback
        return $locales[$locale] ?? $english;
    }
}
