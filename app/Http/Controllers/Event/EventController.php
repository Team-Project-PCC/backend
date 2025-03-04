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

class EventController extends Controller
{
    public function index()
    {
        try {
            $events = Event::with([
                'ticket_categories',
                'event_schedules_recurring.scheduleDays',
                'event_schedules_special',
                'event_images'
            ])->get();

            if ($events->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No events found'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'events' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $event = Event::with([
                'ticket_categories',
                'event_schedules_recurring.scheduleDays',
                'event_schedules_special',
                'event_images'
            ])->findOrFail($id);

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
            'schedule'    => 'required',
            'category'    => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Pastikan schedule & category dalam bentuk array
        $schedules = is_array($request->schedule) ? $request->schedule : json_decode($request->schedule, true);
        $categories = is_array($request->category) ? $request->category : json_decode($request->category, true);

        if (!is_array($schedules) || !is_array($categories)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schedule and category must be valid JSON arrays'
            ], 400);
        }

        // Simpan event
        $event = Event::create($request->only(['title', 'description', 'status', 'type']));

        // Simpan jadwal
        foreach ($schedules as $schedule) {
            if ($event->type == 'recurring') {
                $recurring = EventScheduleRecurring::create([
                    'event_id'       => $event->id,
                    'recurring_type' => $schedule['recurring_type'] ?? null,
                    'start_time'     => $schedule['start_time'] ?? null,
                    'end_time'       => $schedule['end_time'] ?? null
                ]);

                // Simpan hari jika ada
                if (!empty($schedule['days']) && is_array($schedule['days'])) {
                    foreach ($schedule['days'] as $day) {
                        EventScheduleDays::create([
                            'event_schedule_recurring_id' => $recurring->id,
                            'day' => $day
                        ]);
                    }
                }
            } else {
                EventScheduleSpecial::create([
                    'event_id'   => $event->id,
                    'start_date' => $schedule['start_date'] ?? null,
                    'end_date'   => $schedule['end_date'] ?? null,
                    'start_time' => $schedule['start_time'] ?? null,
                    'end_time'   => $schedule['end_time'] ?? null
                ]);
            }
        }

        // Simpan gambar
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
                'category' => $category['category'] ?? null,
                'price'    => $category['price'] ?? 0,
                'quota'    => $category['quota'] ?? 0
            ]);
        }

        return response()->json([
            'status' => 'success',
            'event'  => Event::with([
                'ticket_categories', 
                'event_schedules_recurring.scheduleDays', 
                'event_schedules_special', 
                'event_images'
            ])->find($event->id)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function update(Request $request, $id)
    {
        try {
            $event = Event::findOrFail($id);
            $event->update($request->only(['title', 'description', 'status', 'type']));

            // Hapus schedule lama
            if ($event->type == 'recurring') {
                $schedules = EventScheduleRecurring::where('event_id', $event->id)->get();
                foreach ($schedules as $schedule) {
                    EventScheduleDays::where('event_schedule_recurring_id', $schedule->id)->delete();
                    $schedule->delete();
                }
            } else {
                EventScheduleSpecial::where('event_id', $event->id)->delete();
            }

            // Simpan schedule baru
            $this->store($request);

            return response()->json([
                'status' => 'success',
                'message' => 'Event updated successfully',
                'event' => Event::with(['ticket_categories', 'event_schedules_recurring.scheduleDays', 'event_schedules_special', 'event_images'])->find($event->id)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found'
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $event = Event::findOrFail($id);
            $event->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Event deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found'
            ], 404);
        }
    }
}
