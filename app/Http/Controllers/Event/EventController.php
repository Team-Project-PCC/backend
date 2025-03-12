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

            if($schedule == 'day'){
                $event = $event->whereHas('event_schedules_recurring.scheduleDays');
            } else if($schedule == 'weekly'){
                $event = $event->whereHas('event_schedules_recurring.scheduleWeekly');
            } else if($schedule == 'monthly'){
                $event = $event->whereHas('event_schedules_recurring.scheduleMonthly');
            } else if($schedule == 'yearly'){
                $event = $event->whereHas('event_schedules_recurring.scheduleYearly');
            } else if($schedule == 'special'){
                $event = $event->whereHas('event_schedules_special');
            } else if($schedule == 'recurring'){
                $event = $event->whereHas('event_schedules_recurring');
            } else if($schedule == 'open'){
                $event = $event->where('status', 'published');
            } else if($schedule == 'closed'){
                $event = $event->where('status', 'closed');
            } else if($schedule == 'draft'){
                $event = $event->where('status', 'draft');
            } else {
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
                'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'status'      => 'required|in:draft,published,closed',
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
            
        } catch (AuthorizationException $e) {
            Log::error($e);
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: ' . $e->getMessage()
            ], 403);
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
                'title'       => 'required|string',
                'description' => 'required|string',
                'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'status'      => 'required|in:draft,published,closed',
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

            $event = Event::findOrFail($id);
            $event->update($request->only(['title', 'description', 'status', 'type']));

            // Hapus jadwal lama
            EventScheduleRecurring::where('event_id', $event->id)->delete();
            EventScheduleSpecial::where('event_id', $event->id)->delete();

            // Simpan jadwal baru
            $schedules = json_decode($request->schedule, true);
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
                    } elseif ($schedule['recurring_type'] == 'monthly') {
                        EventScheduleMonthly::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'day' => $schedule['day'],
                            'start_time' => $schedule['start_time'] ?? null,
                            'end_time' => $schedule['end_time'] ?? null,
                        ]);
                    } elseif ($schedule['recurring_type'] == 'yearly') {
                        EventScheduleYearly::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'day' => $schedule['day'],
                            'month' => $schedule['month'],
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

            // Hapus gambar lama jika ada gambar baru
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

            // Hapus kategori tiket lama & tambahkan yang baru
            TicketCategory::where('event_id', $event->id)->delete();
            $categories = json_decode($request->category, true);
            foreach ($categories as $category) {
                TicketCategory::create([
                    'event_id' => $event->id,
                    'category' => $category['category'] ?? 'General',
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
                'message' => 'Event successfully updated',
                'event'  => $event->load(['ticket_categories', 'event_schedules_recurring.scheduleDays', 'event_schedules_special', 'event_images'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
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
