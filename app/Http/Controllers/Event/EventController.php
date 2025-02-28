<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Validator;
use App\Models\Event_Schedules_Recurring;
use App\Models\Event_Schedules_Special;
use App\Models\Ticket_Categories;

class EventController extends Controller
{
    public function index()
    {
        try{
            $events = Event::all();
        if($events->where('type', 'recurring')){
            return response()->json([
                'status' => 'success',
                'event' => $events,
                'category' => $events->ticket_categories,
                'schedule' => $events->event_schedules_recurring
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'event' => $events,
                'category' => $events->ticket_categories,
                'schedule' => $events->event_schedules_special
            ]);
        }
        } catch (\Exception $e){
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
                return response()->json([
                    'status' => 'success',
                    'event' => $event,
                    'category' => $event->ticket_categories,
                    'schedule' => $event->event_schedules_recurring
                ]);
            } else {
                return response()->json([
                    'status' => 'success',
                    'event' => $event,
                    'category' => $event->ticket_categories,
                    'schedule' => $event->event_schedules_special
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
        try{
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'required|string',
                'image' => 'required|string',
                'status' => 'required|in:draft,published,closed',
                'type' => 'required|in:recurring,special',
                'schedule' => 'required|array',
                'category' => 'required|array',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            $event = Event::create([
                'title' => $request->title,
                'description' => $request->description,
                'image' => $request->image,
                'status' => $request->status,
                'type' => $request->type
            ]);
    
            foreach ($request->schedule as $schedule) {
                if ($event->type == 'recurring') {
                    Event_Schedules_Recurring::create([
                        'event_id' => $event->id,
                        'recurring_type' => $schedule['recurring_type'],
                        'day' => $schedule['day'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ]);
                } else {
                    Event_Schedules_Special::create([
                        'event_id' => $event->id,
                        'start_date' => $schedule['start_date'],
                        'end_date' => $schedule['end_date'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ]);
                }
            }
    
            foreach ($request->category as $category) {
                Ticket_Categories::create([
                    'event_id' => $event->id,
                    'category' => $category['category'],
                    'price' => $category['price'],
                    'quota' => $category['quota']
                ]);
            }
    
            return response()->json([
                'status' => 'success',
                'message' => 'Event created successfully',
                'event' => $event
            ], 201);
        } catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
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
                    Event_Schedules_Recurring::create([
                        'event_id' => $event->id,
                        'recurring_type' => $schedule['recurring_type'],
                        'day' => $schedule['day'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ]);
                } else {
                    Event_Schedules_Special::create([
                        'event_id' => $event->id,
                        'start_date' => $schedule['start_date'],
                        'end_date' => $schedule['end_date'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ]);
                }
            }
    
            Ticket_Categories::where('event_id', $event->id)->delete();
    
            foreach ($request->category as $category) {
                Ticket_Categories::create([
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
