<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    public function run()
    {
        $events = [
            [
                'title' => 'Wayang Kulit Malam',
                'description' => 'Pertunjukan wayang kulit dengan dalang terkenal.',
                'type' => 'recurring',
                'status' => 'open',
                'schedules' => [
                    ['recurring_type' => 'weekly', 'day' => [4, 5, 7], 'start_time' => '10:00', 'end_time' => '16:00']
                ],
                'categories' => [
                    ['category' => 'child', 'price' => 3000, 'quota' => 20],
                    ['category' => 'regular', 'price' => 6000, 'quota' => 50],
                    ['category' => 'student', 'price' => 4000, 'quota' => 40],
                    ['category' => 'vip', 'price' => 12000, 'quota' => 15],
                ],
            ],
            [
                'title' => 'Teater Tradisional',
                'description' => 'Drama klasik khas daerah.',
                'type' => 'recurring',
                'status' => 'open',
                'schedules' => [
                    ['recurring_type' => 'daily', 'start_time' => '14:00', 'end_time' => '18:00']
                ],
                'categories' => [
                    ['category' => 'regular', 'price' => 5000, 'quota' => 30],
                    ['category' => 'vip', 'price' => 10000, 'quota' => 10],
                ],
            ],
            [
                'title' => 'Konser Musik Daerah',
                'description' => 'Menampilkan musik khas daerah.',
                'type' => 'recurring',
                'status' => 'open',
                'schedules' => [
                    ['recurring_type' => 'weekly', 'day' => [2, 4], 'start_time' => '18:00', 'end_time' => '22:00']
                ],
                'categories' => [
                    ['category' => 'child', 'price' => 4000, 'quota' => 25],
                    ['category' => 'regular', 'price' => 8000, 'quota' => 40],
                ],
            ],
            [
                'title' => 'Festival Tari Tradisional',
                'description' => 'Pagelaran tari daerah.',
                'type' => 'recurring',
                'status' => 'open',
                'schedules' => [
                    ['recurring_type' => 'weekly', 'day' => [1], 'start_time' => '17:00', 'end_time' => '20:00']
                ],
                'categories' => [
                    ['category' => 'regular', 'price' => 5000, 'quota' => 60],
                    ['category' => 'vip', 'price' => 15000, 'quota' => 20],
                ],
            ],
        ];

        foreach ($events as $eventData) {
            // Insert event
            $eventId = DB::table('events')->insertGetId([
                'title' => $eventData['title'],
                'description' => $eventData['description'],
                'type' => $eventData['type'],
                'status' => $eventData['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert schedule
            foreach ($eventData['schedules'] as $schedule) {
                $recurringId = DB::table('event_schedules_recurrings')->insertGetId([
                    'event_id' => $eventId,
                    'recurring_type' => $schedule['recurring_type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($schedule['recurring_type'] === 'weekly') {
                    foreach ($schedule['day'] as $day) {
                        DB::table('event_schedule_weeklies')->insert([
                            'event_schedule_recurring_id' => $recurringId,
                            'day' => strtolower($day),
                            'start_time' => $schedule['start_time'],
                            'end_time' => $schedule['end_time'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } elseif ($schedule['recurring_type'] === 'daily') {
                    DB::table('event_schedule_days')->insert([
                        'event_schedule_recurring_id' => $recurringId,
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Insert ticket categories
            foreach ($eventData['categories'] as $category) {
                DB::table('ticket_categories')->insert([
                    'event_id' => $eventId,
                    'category' => $category['category'],
                    'price' => $category['price'],
                    'quota' => $category['quota'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
