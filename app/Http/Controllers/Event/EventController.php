<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Validator;
use App\Models\EventScheduleRecurring;
use App\Models\EventScheduleSpecial;
use App\Models\EventScheduleDays;
use App\Models\TicketCategory;
use App\Models\EventImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\EventScheduleMonthly;
use App\Models\EventScheduleYearly;
use Illuminate\Auth\Access\AuthorizationException;

class EventController extends Controller
{
    public function index()
    {
        try {
            $events = Event::with([
                'ticket_categories',
                'event_schedules_recurring.scheduleDays',
                'event_schedules_recurring.scheduleWeekly',
                'event_schedules_recurring.scheduleMonthly',
                'event_schedules_recurring.scheduleYearly',
                'event_schedules_special',
                'event_images'
            ])->get();

            foreach ($events as $event) {
                if ($event->ticket_categories->isEmpty()) {
                    $event->unsetRelation('ticket_categories');
                }
                if ($event->event_schedules_recurring->isEmpty()) {
                    $event->unsetRelation('event_schedules_recurring');
                }
                if ($event->event_schedules_special->isEmpty()) {
                    $event->unsetRelation('event_schedules_special');
                }
                if ($event->event_images->isEmpty()) {
                    $event->unsetRelation('event_images');
                }

                if ($event->relationLoaded('event_schedules_recurring')) {
                    foreach ($event->event_schedules_recurring as $recurring) {
                        if ($recurring->scheduleDays->isEmpty()) {
                            $recurring->unsetRelation('scheduleDays');
                        }
                        if ($recurring->scheduleWeekly->isEmpty()) {
                            $recurring->unsetRelation('scheduleWeekly');
                        }
                        if ($recurring->scheduleMonthly->isEmpty()) {
                            $recurring->unsetRelation('scheduleMonthly');
                        }
                        if ($recurring->scheduleYearly->isEmpty()) {
                            $recurring->unsetRelation('scheduleYearly');
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Events retrieved successfully',
                'events'  => $events
            ], 200);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $event = Event::with([
                'ticket_categories',
                'event_schedules_recurring.scheduleDays',
                'event_schedules_recurring.scheduleWeekly',
                'event_schedules_recurring.scheduleMonthly',
                'event_schedules_recurring.scheduleYearly',
                'event_schedules_special',
                'event_images'
            ])->find($id);

            if (!$event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found'
                ], 404);
            }            

            if ($event->ticket_categories->isEmpty()) {
                $event->unsetRelation('ticket_categories');
            }
            if ($event->event_schedules_recurring->isEmpty()) {
                $event->unsetRelation('event_schedules_recurring');
            }
            if ($event->event_schedules_special->isEmpty()) {
                $event->unsetRelation('event_schedules_special');
            }
            if ($event->event_images->isEmpty()) {
                $event->unsetRelation('event_images');
            }

            if ($event->relationLoaded('event_schedules_recurring')) {
                foreach ($event->event_schedules_recurring as $recurring) {
                    if ($recurring->scheduleDays->isEmpty()) {
                        $recurring->unsetRelation('scheduleDays');
                    }
                    if ($recurring->scheduleWeekly->isEmpty()) {
                        $recurring->unsetRelation('scheduleWeekly');
                    }
                    if ($recurring->scheduleMonthly->isEmpty()) {
                        $recurring->unsetRelation('scheduleMonthly');
                    }
                    if ($recurring->scheduleYearly->isEmpty()) {
                        $recurring->unsetRelation('scheduleYearly');
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found'
            ], 404);
        }
    }

    public function show_schedule($schedule){
        try{
            $event = Event::with([
                'ticket_categories',
                'event_schedules_recurring.scheduleDays',
                'event_schedules_recurring.scheduleWeekly',
                'event_schedules_recurring.scheduleMonthly',
                'event_schedules_recurring.scheduleYearly',
                'event_schedules_special',
                'event_images'
            ]);

            $event = match ($schedule) {
                'day' => Event::whereHas('event_schedules_recurring.scheduleDays'),
                'weekly' => Event::whereHas('event_schedules_recurring.scheduleWeekly'),
                'monthly' => Event::whereHas('event_schedules_recurring.scheduleMonthly'),
                'yearly' => Event::whereHas('event_schedules_recurring.scheduleYearly'),
                'special' => Event::whereHas('event_schedules_special'),
                'recurring' => Event::whereHas('event_schedules_recurring'),
                'open' => Event::where('status', 'open'),
                'close' => Event::where('status', 'close'),
                default => null,
            };
            
            if (!$event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid schedule type'
                ], 400);
            }            

            $events = $event->get();

            return response()->json([
                'status' => 'success',
                'events' => $events
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'title'       => 'required|string',
                'description' => 'required|string',
                'images'      => 'nullable|array',
                'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'status'      => 'required|in:open,close',
                'type'        => 'required|in:recurring,special',
                'schedule'    => 'required|json',
                'category'    => 'required|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Decode JSON input
            $schedules = json_decode($request->schedule, true);
            $categories = json_decode($request->category, true);

            if (!is_array($schedules) || !is_array($categories)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Schedule and category must be valid JSON arrays'
                ], 400);
            }

            $event = Event::create($request->only(['title', 'description', 'status', 'type']));

            foreach ($schedules as $schedule) {
                if ($event->type == 'recurring') {
                    $recurring = EventScheduleRecurring::create([
                        'event_id'       => $event->id,
                        'recurring_type' => $schedule['recurring_type'],
                    ]);

                    if($schedule['recurring_type'] == 'weekly'){
                        EventScheduleDays::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'days' => $schedule['days'],
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                    } else if($schedule['recurring_type'] == 'monthly') {
                        EventScheduleMonthly::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'day' => $schedule['day'],
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                    } else if($schedule['recurring_type'] == 'yearly') {
                        EventScheduleYearly::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'day' => $schedule['day'],
                            'month' => $schedule['month'],
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                        
                    } else if($schedule['recurring_type'] == 'daily') {
                        EventScheduleDays::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                    }
                } else {
                    EventScheduleSpecial::create([
                        'event_id'   => $event->id,
                        'start_datetime' => $schedule['start_datetime'] ?? now(),
                        'end_datetime'   => $schedule['end_datetime'] ?? now()->addHours(1),
                    ]);
                }
            }

            // Simpan gambar jika ada
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = 'event_' . $event->id . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('events', $filename, 'public');
                    $imageUrl = asset('storage/' . $path);

                    EventImage::create([
                        'event_id' => $event->id,
                        'name' => $filename,
                        'url' => $imageUrl
                    ]);
                }
            }

            // Simpan kategori tiket
            foreach ($categories as $category) {
                TicketCategory::create([
                    'event_id' => $event->id,
                    'category' => $category['category'] ?? 'General',
                    'description' => $category['description'] ?? null,
                    'price'    => $category['price'] ?? 0,
                    'quota'    => $category['quota'] ?? 0
                ]);
            }

            $event = Event::with([
                'ticket_categories',
                'event_schedules_recurring.scheduleDays',
                'event_schedules_recurring.scheduleWeekly',
                'event_schedules_recurring.scheduleMonthly',
                'event_schedules_recurring.scheduleYearly',
                'event_schedules_special',
                'event_images'
            ])->find($event->id);

            if ($event->ticket_categories->isEmpty()) {
                $event->unsetRelation('ticket_categories');
            }
            if ($event->event_schedules_recurring->isEmpty()) {
                $event->unsetRelation('event_schedules_recurring');
            }
            if ($event->event_schedules_special->isEmpty()) {
                $event->unsetRelation('event_schedules_special');
            }
            if ($event->event_images->isEmpty()) {
                $event->unsetRelation('event_images');
            }

            if ($event->relationLoaded('event_schedules_recurring')) {
                foreach ($event->event_schedules_recurring as $recurring) {
                    if ($recurring->scheduleDays->isEmpty()) {
                        $recurring->unsetRelation('scheduleDays');
                    }
                    if ($recurring->scheduleWeekly->isEmpty()) {
                        $recurring->unsetRelation('scheduleWeekly');
                    }
                    if ($recurring->scheduleMonthly->isEmpty()) {
                        $recurring->unsetRelation('scheduleMonthly');
                    }
                    if ($recurring->scheduleYearly->isEmpty()) {
                        $recurring->unsetRelation('scheduleYearly');
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Event successfully created',
                'event'  => $event
            ], 201);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateEvent(Request $request, $id)
{
    try {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'images'      => 'nullable|array',
            'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status'      => 'sometimes|required|in:open,close',
            'type'        => 'sometimes|required|in:recurring,special',
            'schedule'    => 'nullable|json',
            'category'    => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Cari event yang akan diperbarui
        $event = Event::findOrFail($id);
        $event->update($request->only(['title', 'description', 'status', 'type']));

        // Perbarui jadwal jika ada
        if ($request->has('schedule')) {
            $schedules = json_decode($request->schedule, true);
            if (!is_array($schedules)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Schedule must be a valid JSON array'
                ], 400);
            }

            // Hapus jadwal lama
            EventScheduleRecurring::where('event_id', $event->id)->delete();
            EventScheduleSpecial::where('event_id', $event->id)->delete();

            foreach ($schedules as $schedule) {
                if ($event->type == 'recurring') {
                    $recurring = EventScheduleRecurring::create([
                        'event_id'       => $event->id,
                        'recurring_type' => $schedule['recurring_type'],
                    ]);

                    if ($schedule['recurring_type'] == 'weekly') {
                        EventScheduleDays::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'days' => $schedule['days'],
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                    } else if ($schedule['recurring_type'] == 'monthly') {
                        EventScheduleMonthly::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'day' => $schedule['day'],
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                    } else if ($schedule['recurring_type'] == 'yearly') {
                        EventScheduleYearly::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'day' => $schedule['day'],
                            'month' => $schedule['month'],
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                    } else if ($schedule['recurring_type'] == 'daily') {
                        EventScheduleDays::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                    }
                } else {
                    EventScheduleSpecial::create([
                        'event_id'   => $event->id,
                        'start_datetime' => $schedule['start_datetime'] ?? now(),
                        'end_datetime'   => $schedule['end_datetime'] ?? now()->addHours(1),
                    ]);
                }
            }
        }

        // Perbarui kategori tiket jika ada
        if ($request->has('category')) {
            $categories = json_decode($request->category, true);
            if (!is_array($categories)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category must be a valid JSON array'
                ], 400);
            }
            TicketCategory::where('event_id', $event->id)->delete();
            foreach ($categories as $category) {
                TicketCategory::create([
                    'event_id' => $event->id,
                    'category' => $category['category'] ?? 'General',
                    'description' => $category['description'] ?? null,
                    'price'    => $category['price'] ?? 0,
                    'quota'    => $category['quota'] ?? 0
                ]);
            }
        }

        // Perbarui gambar jika ada
        if ($request->hasFile('images')) {
            EventImage::where('event_id', $event->id)->delete();
            foreach ($request->file('images') as $image) {
                $filename = 'event_' . $event->id . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('events', $filename, 'public');
                $imageUrl = asset('storage/' . $path);

                EventImage::create([
                    'event_id' => $event->id,
                    'name' => $filename,
                    'url' => $imageUrl
                ]);
            }
        }

        // Ambil data terbaru
        $event = Event::with([
            'ticket_categories',
            'event_schedules_recurring.scheduleDays',
            'event_schedules_recurring.scheduleWeekly',
            'event_schedules_recurring.scheduleMonthly',
            'event_schedules_recurring.scheduleYearly',
            'event_schedules_special',
            'event_images'
        ])->find($event->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Event successfully updated',
            'event'  => $event
        ], 200);
    } catch (\Exception $e) {
        Log::error($e);
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ], 500);
    }
}


    public function update(Request $request, $id)
{
    try {
        $validator = Validator::make($request->all(), [
            'title'       => 'nullable|string',
            'description' => 'nullable|string',
            'images'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status'      => 'nullable|in:open,close',
            'type'        => 'nullable|in:recurring,special',
            'schedule'    => 'nullable|json',
            'category'    => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $event = Event::findOrFail($id);
        $event->update($request->only(['title', 'description', 'status', 'type']));

        // Hapus jadwal lama jika ada input jadwal baru
        if ($request->has('schedule')) {
            EventScheduleRecurring::where('event_id', $event->id)->delete();
            EventScheduleSpecial::where('event_id', $event->id)->delete();

            // Decode JSON dengan validasi
            $schedules = json_decode($request->schedule, true);
            if (is_array($schedules)) {
                foreach ($schedules as $schedule) {
                    if ($event->type == 'recurring' && isset($schedule['recurring_type'])) {
                        $recurring = EventScheduleRecurring::create([
                            'event_id'       => $event->id,
                            'recurring_type' => $schedule['recurring_type'],
                        ]);

                        if ($schedule['recurring_type'] == 'weekly' && isset($schedule['days'])) {
                            EventScheduleDays::create([
                                'event_schedule_recurring_id' => $recurring->id,
                                'days' => json_encode($schedule['days']),
                                'start_time' => $schedule['start_time'] ?? null,
                                'end_time' => $schedule['end_time'] ?? null,
                            ]);
                        } elseif ($schedule['recurring_type'] == 'monthly' && isset($schedule['day'])) {
                            EventScheduleMonthly::create([
                                'event_schedule_recurring_id' => $recurring->id,
                                'day' => $schedule['day'],
                                'start_time' => $schedule['start_time'] ?? null,
                                'end_time' => $schedule['end_time'] ?? null,
                            ]);
                        } elseif ($schedule['recurring_type'] == 'yearly' && isset($schedule['day'], $schedule['month'])) {
                            EventScheduleYearly::create([
                                'event_schedule_recurring_id' => $recurring->id,
                                'day' => $schedule['day'],
                                'month' => $schedule['month'],
                                'start_time' => $schedule['start_time'] ?? null,
                                'end_time' => $schedule['end_time'] ?? null,
                            ]);
                        }
                    } elseif ($event->type == 'special' && isset($schedule['start_datetime'], $schedule['end_datetime'])) {
                        EventScheduleSpecial::create([
                            'event_id'   => $event->id,
                            'start_datetime' => $schedule['start_datetime'],
                            'end_datetime'   => $schedule['end_datetime'],
                        ]);
                    }
                }
            }
        }

        // Hapus gambar lama jika ada gambar baru
        Log::info($request->images);
        if ($request->hasFile('images')) {
            EventImage::where('event_id', $event->id)->delete();
            foreach ($request->file('images') as $image) {
                $filename = 'event_' . $event->id . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('events', $filename, 'public');
                $imageUrl = asset('storage/' . $path);

                EventImage::create([
                    'event_id' => $event->id,
                    'name' => $filename,
                    'url' => $imageUrl
                ]);
            }
        }

        // Hapus kategori lama & tambahkan yang baru jika ada input kategori baru
        if ($request->has('category')) {
            TicketCategory::where('event_id', $event->id)->delete();

            $categories = json_decode($request->category, true);
            if (is_array($categories)) {
                foreach ($categories as $category) {
                    TicketCategory::create([
                        'event_id' => $event->id,
                        'category' => $category['category'] ?? 'General',
                        'description' => $category['description'] ?? null,
                        'price'    => $category['price'] ?? 0,
                        'quota'    => $category['quota'] ?? 0
                    ]);
                }
            }
        }

        // Ambil data lengkap setelah update
        $event = Event::with([
            'ticket_categories',
            'event_schedules_recurring.scheduleDays',
            'event_schedules_recurring.scheduleMonthly',
            'event_schedules_recurring.scheduleYearly',
            'event_schedules_special',
            'event_images'
        ])->find($event->id);

        // Hapus relasi kosong untuk menghindari error
        if ($event->ticket_categories->isEmpty()) {
            $event->unsetRelation('ticket_categories');
        }
        if ($event->event_schedules_recurring->isEmpty()) {
            $event->unsetRelation('event_schedules_recurring');
        }
        if ($event->event_schedules_special->isEmpty()) {
            $event->unsetRelation('event_schedules_special');
        }
        if ($event->event_images->isEmpty()) {
            $event->unsetRelation('event_images');
        }

        // Periksa dan hapus relasi kosong dalam `event_schedules_recurring`
        if ($event->relationLoaded('event_schedules_recurring')) {
            foreach ($event->event_schedules_recurring as $recurring) {
                if ($recurring->scheduleDays->isEmpty()) {
                    $recurring->unsetRelation('scheduleDays');
                }
                if ($recurring->scheduleMonthly->isEmpty()) {
                    $recurring->unsetRelation('scheduleMonthly');
                }
                if ($recurring->scheduleYearly->isEmpty()) {
                    $recurring->unsetRelation('scheduleYearly');
                }
            }
        }

        Log::info("Request data: ", $request->all());
        Log::info("Uploaded files: ", $request->file());


        return response()->json([
            'status' => 'success',
            'message' => 'Event berhasil diperbarui',
            'data' => $event
        ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $event = Event::findOrFail($id);

            // Hapus semua relasi terkait
            EventScheduleRecurring::where('event_id', $event->id)->delete();
            EventScheduleSpecial::where('event_id', $event->id)->delete();
            TicketCategory::where('event_id', $event->id)->delete();
            
            // Hapus gambar dari storage dan database
            $images = EventImage::where('event_id', $event->id)->get();
            foreach ($images as $image) {
                Storage::disk('public')->delete('events/' . $image->name);
                $image->delete();
            }

            $event->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Event successfully deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found'
            ], 404);
        }
    }

}
