<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        try {
            $events = Event::all();
            return response()->json($events, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get events',
            ], 500);
        }
    }

    public function show($name)
    {
        try {
            $event = Event::where('name', $name)->first();
            if (!$event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                ], 404);
            }
            return response()->json([
                'event' => $event,
                'cover_images' => $event->images->where('is_featured', 1),
                'images' => $event->images,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get event',
            ], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $event = Event::create([
                'museum_id' => $request->museum_id,
                'name' => $request->name,
                'description' => $request->description,
                'date' => $request->date,
                'price' => $request->price,
                'image' => $request->image,
            ]);
            return response()->json($event, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create event',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $event = Event::find($id);
            if (!$event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                ], 404);
            }
            $event->update([
                'museum_id' => $request->museum_id,
                'name' => $request->name,
                'description' => $request->description,
                'date' => $request->date,
                'price' => $request->price,
                'image' => $request->image,
            ]);
            return response()->json($event, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update event',
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $event = Event::find($id);
            if (!$event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                ], 404);
            }
            $event->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Event deleted',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete event',
            ], 500);
        }
    }
}
