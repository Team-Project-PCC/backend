<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Validator;
use App\Models\Event_Schedules_Recurring;
use App\Models\Event_Schedules_Special;
use App\Models\TicketCategory;
use Illuminate\Support\Facades\Http;
use App\Models\Event_Image;

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
            'title' => 'required|string',
            'description' => 'required|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:draft,published,closed',
            'type' => 'required|in:recurring,special',
            'schedule' => 'required', 
            'category' => 'required', 
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
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'type' => $request->type
        ]);

        foreach ($schedules as $schedule) {
            if ($event->type == 'recurring') {
                Event_Schedules_Recurring::create([
                    'event_id' => $event->id,
                    'recurring_type' => $schedule['recurring_type'] ?? null,
                    'day' => $schedule['day'] ?? null,
                    'start_time' => $schedule['start_time'] ?? null,
                    'end_time' => $schedule['end_time'] ?? null
                ]);
            } else {
                Event_Schedules_Special::create([
                    'event_id' => $event->id,
                    'start_date' => $schedule['start_date'] ?? null,
                    'end_date' => $schedule['end_date'] ?? null,
                    'start_time' => $schedule['start_time'] ?? null,
                    'end_time' => $schedule['end_time'] ?? null
                ]);
            }
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension(); 
            $newFileName = 'event_' . $event->id . '_' . uniqid() . '.' . $extension;

            $image = file_get_contents($file->getRealPath());
            $encodedImage = base64_encode($image);
        
            $response = Http::asForm()->post('https://api.imgbb.com/1/upload', [
                'key' => env('IMGBB_API_KEY'),
                'image' => $encodedImage
            ]);
        
            $result = $response->json();
            if (isset($result['data']['url'])) {
                $imageUrl = $result['data']['url'];
            }
        }

        if ($imageUrl) {
            Event_Image::create([
                'event_id' => $event->id,
                'name' => $newFileName,
                'url' => $imageUrl
            ]);
        }

        foreach ($categories as $category) {
            TicketCategory::create([
                'event_id' => $event->id,
                'category' => $category['category'] ?? null,
                'price' => $category['price'] ?? 0,
                'quota' => $category['quota'] ?? 0
            ]);
        }

        if($event->type == 'recurring'){
            $event = Event::with(['ticket_categories', 'event_schedules_recurring', 'event_images'])->find($event->id);
            return response()->json([
                'status' => 'success',
                'event' => $event,
            ]);
        } else {
            $event = Event::with(['ticket_categories', 'event_schedules_special', 'event_images'])->find($event->id);
            return response()->json([
                'status' => 'success',
                'event' => $event,
            ]);
        }
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
                Event_Schedules_Recurring::where('event_id', $event->id)->delete();
            } else {
                Event_Schedules_Special::where('event_id', $event->id)->delete();
            }
    
            foreach ($request->schedule as $schedule) {
                if ($event->type == 'recurring') {
                    Event_Schedules_Recurring::update([
                        'event_id' => $event->id,
                        'recurring_type' => $schedule['recurring_type'],
                        'day' => $schedule['day'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ]);
                } else {
                    Event_Schedules_Special::update([
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
