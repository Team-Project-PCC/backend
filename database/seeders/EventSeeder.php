<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $events = [
            [
                'title'       => 'Pameran Lukisan Abstrak 2025',
                'description' => 'Eksplorasi dunia seni abstrak oleh seniman kontemporer ternama.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2025-05-10',
                'end_date'    => '2025-05-20',
            ],
            [
                'title'       => 'Konser Musik Klasik Malam',
                'description' => 'Malam spesial dengan orkestra klasik terbaik di dunia.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2025-06-15',
                'end_date'    => '2025-06-15',
            ],
            [
                'title'       => 'Festival Jazz & Blues 2025',
                'description' => 'Menampilkan musisi jazz dan blues internasional.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2025-07-05',
                'end_date'    => '2025-07-07',
            ],
            [
                'title'       => 'Museum Night Tour: Sejarah Musik Dunia',
                'description' => 'Tur malam eksklusif tentang perjalanan sejarah musik dari zaman klasik hingga modern.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2025-08-12',
                'end_date'    => '2025-08-12',
            ],
            [
                'title'       => 'Seni Digital & NFT Expo',
                'description' => 'Pameran seni digital dan NFT dari seniman inovatif.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2025-09-20',
                'end_date'    => '2025-09-22',
            ],
            [
                'title'       => 'Workshop Melukis Bareng Seniman',
                'description' => 'Belajar teknik melukis langsung dari pelukis profesional.',
                'status'      => 'published',
                'type'        => 'recurring',
                'recurring_type' => 'weekly',
            ],
            [
                'title'       => 'Pameran Alat Musik Tradisional Dunia',
                'description' => 'Koleksi langka alat musik tradisional dari berbagai budaya.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2025-10-05',
                'end_date'    => '2025-10-15',
            ],
            [
                'title'       => 'Senja Musik Akustik di Museum',
                'description' => 'Konser akustik intim di tengah galeri seni museum.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2025-11-10',
                'end_date'    => '2025-11-10',
            ],
            [
                'title'       => 'Seni Grafiti & Street Art Festival',
                'description' => 'Seni jalanan dari berbagai seniman grafiti berbakat.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2025-12-03',
                'end_date'    => '2025-12-05',
            ],
            [
                'title'       => 'Museum Film & Soundtrack Exhibition',
                'description' => 'Eksplorasi sejarah film dan musik soundtrack ikonik.',
                'status'      => 'published',
                'type'        => 'special',
                'start_date'  => '2026-01-15',
                'end_date'    => '2026-01-20',
            ],
        ];

        foreach ($events as $event) {
            $eventId = DB::table('events')->insertGetId([
                'title'       => $event['title'],
                'description' => $event['description'],
                'status'      => $event['status'],
                'type'        => $event['type'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Jika event memiliki tanggal, masukkan ke event_schedules_special
            if ($event['type'] == 'special') {
                DB::table('event_schedules_specials')->insert([
                    'event_id'   => $eventId,
                    'start_date' => $event['start_date'],
                    'end_date'   => $event['end_date'],
                    'start_time' => '10:00',
                    'end_time'   => '18:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Jika event recurring, masukkan ke event_schedules_recurring
                DB::table('event_schedules_recurrings')->insert([
                    'event_id'       => $eventId,
                    'recurring_type' => $event['recurring_type'],
                    'day'            => 'Saturday',
                    'start_time'     => '14:00',
                    'end_time'       => '16:00',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            // Tambahkan kategori tiket untuk setiap event
            DB::table('ticket_categories')->insert([
                [
                    'event_id'  => $eventId,
                    'category'  => 'VIP',
                    'price'     => 150000,
                    'quota'     => 100,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ],
                [
                    'event_id'  => $eventId,
                    'category'  => 'Regular',
                    'price'     => 75000,
                    'quota'     => 300,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]
            ]);

            // Tambahkan gambar dummy untuk event
            DB::table('event_images')->insert([
                [
                    'event_id' => $eventId,
                    'name'     => 'event_' . $eventId . '_1.jpg',
                    'url'      => url('storage/events/event_' . $eventId . '_1.jpg'),
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ],
                [
                    'event_id' => $eventId,
                    'name'     => 'event_' . $eventId . '_2.jpg',
                    'url'      => url('storage/events/event_' . $eventId . '_2.jpg'),
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]
            ]);
        }

        echo "Seeder event berhasil dijalankan!\n";
    }
}
