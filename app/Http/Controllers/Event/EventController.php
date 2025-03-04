<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Validator;
use App\Models\EventSchedulesRecurring;
use App\Models\EventSchedulesSpecial;
use App\Models\TicketCategory;
use Illuminate\Support\Facades\Http;
use App\Models\EventImage;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function index()
    {
        try {
            $events = Event::get();
            if($events->isEmpty()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'No events found'
                ]);
            } 
            if($events->first()->type == 'recurring'){
                $events = Event::with(['ticket_categories', 'event_schedules_recurring'])->get();
                return response()->json([
                    'status' => 'success',
                    'events' => $events
                ]);
            } else {
                $events = Event::with(['ticket_categories', 'event_schedules_special'])->get();
                return response()->json([
                    'status' => 'success',
                    'events' => $events
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }



    public function show($id)
    {
        try{
            $event = Event::find($id);
            if($event->type == 'recurring'){
                $event = Event::with(['ticket_categories', 'event_schedules_recurring'])->find($id);
                return response()->json([
                    'status' => 'success',
                    'event' => $event,
                ]);
            } else {
                $event = Event::with(['ticket_categories', 'event_schedules_special'])->find($id);
                return response()->json([
                    'status' => 'success',
                    'event' => $event,
                ]);
            }
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'title'       => 'required|string',
                'description' => 'required|string',
                'images.*'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Support multiple images
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

            $schedules = is_array($request->schedule) ? $request->schedule : json_decode($request->schedule, true);
            $categories = is_array($request->category) ? $request->category : json_decode($request->category, true);

            if (!is_array($schedules) || !is_array($categories)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Schedule and Category must be a valid JSON array'
                ], 400);
            }

            $event = Event::create([
                'title'       => $request->title,
                'description' => $request->description,
                'status'      => $request->status,
                'type'        => $request->type
            ]);

            $scheduleData = [];
            foreach ($schedules as $schedule) {
                if ($event->type == 'recurring') {
                    $scheduleData[] = [
                        'event_id'       => $event->id,
                        'recurring_type' => $schedule['recurring_type'] ?? null,
                        'day'            => $schedule['day'] ?? null,
                        'start_time'     => $schedule['start_time'] ?? null,
                        'end_time'       => $schedule['end_time'] ?? null,
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ];
                } else {
                    $scheduleData[] = [
                        'event_id'   => $event->id,
                        'start_date' => $schedule['start_date'] ?? null,
                        'end_date'   => $schedule['end_date'] ?? null,
                        'start_time' => $schedule['start_time'] ?? null,
                        'end_time'   => $schedule['end_time'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            if ($event->type == 'recurring') {
                EventSchedulesRecurring::insert($scheduleData);
            } else {
                EventSchedulesSpecial::insert($scheduleData);
            }

            if ($request->hasFile('image')) {
                $images = $request->file('image'); // Bisa single atau multiple file
                $imageUrls = [];
            
                foreach ($images as $image) {
                    $filename = 'event_' . $event->id . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('events', $filename, 'public'); // Simpan ke storage/public/events
                    $imageUrl = asset('storage/' . $path); // Buat URL akses
            
                    // Simpan ke database
                    EventImage::create([
                        'event_id' => $event->id,
                        'name' => $filename,
                        'url' => $imageUrl
                    ]);
            
                    $imageUrls[] = $imageUrl;
                }
            }            

            // Simpan kategori tiket (batch insert)
            $ticketData = [];
            foreach ($categories as $category) {
                $ticketData[] = [
                    'event_id'   => $event->id,
                    'category'   => $category['category'] ?? null,
                    'price'      => $category['price'] ?? 0,
                    'quota'      => $category['quota'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            TicketCategory::insert($ticketData);

            // Ambil data lengkap untuk response
            $event = Event::with([
                'ticket_categories', 
                $event->type == 'recurring' ? 'event_schedules_recurring' : 'event_schedules_special',
                'event_images'
            ])->find($event->id);

            return response()->json([
                'status' => 'success',
                'event' => $event,
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
        try{
            $validator = Validator::make($request->all(), [
                'title' => 'nullable|string',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'status' => 'nullable|in:draft,published,closed',
                'type' => 'nullable|in:recurring,special',
                'schedule' => 'nullable|array',
                'category' => 'nullable|array',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            $event = Event::find($id);
            $event->update([
                'title' => $request->title,
                'description' => $request->description,
                'image' => $request->image,
                'status' => $request->status,
                'type' => $request->type
            ]);
    
            if ($event->type == 'recurring') {
                EventSchedulesRecurring::where('event_id', $event->id)->delete();
            } else {
                EventSchedulesSpecial::where('event_id', $event->id)->delete();
            }
    
            foreach ($request->schedule as $schedule) {
                if ($event->type == 'recurring') {
                    EventSchedulesRecurring::update([
                        'event_id' => $event->id,
                        'recurring_type' => $schedule['recurring_type'],
                        'day' => $schedule['day'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ]);
                } else {
                    EventSchedulesSpecial::update([
                        'event_id' => $event->id,
                        'start_date' => $schedule['start_date'],
                        'end_date' => $schedule['end_date'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ]);
                }
            }
    
            TicketCategory::where('event_id', $event->id)->delete();
    
            foreach ($request->category as $category) {
                TicketCategory::create([
                    'event_id' => $event->id,
                    'category' => $category['category'],
                    'price' => $category['price'],
                    'quota' => $category['quota']
                ]);
            }
    
            return response()->json([
                'status' => 'success',
                'message' => 'Event updated successfully',
                'event' => $event
            ]);
        } catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try{
            $event = Event::find($id);
            $event->delete();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Event deleted successfully'
            ]);
        } catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
