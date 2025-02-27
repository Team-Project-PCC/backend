<?php

namespace App\Http\Controllers\Museum;

use App\Http\Controllers\Controller;
use App\Models\Museum_Image;
use Illuminate\Http\Request;

class MuseumImageController extends Controller
{
    public function index(Request $request)
    {
        $museum_images = Museum_Image::all();

        return response()->json([
            'status' => 'success',
            'data' => $museum_images,
        ]);
    }

    public function show(Request $request, $id)
    {
        $museum_image = Museum_Image::find($id);

        if (!$museum_image) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $museum_image,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'museum_id' => 'required|integer',
            'image' => 'required|image',
            'title' => 'required|string',
            'description' => 'required|string',
            'is_featured' => 'required|boolean',
        ]);

        $image = $request->file('image');
        $image_name = time() . '.' . $image->extension();
        $image->move(public_path('images'), $image_name);

        $museum_image = Museum_Image::create([
            'museum_id' => $request->museum_id,
            'image' => $image_name,
            'title' => $request->title,
            'description' => $request->description,
            'is_featured' => $request->is_featured,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Image uploaded',
            'data' => $museum_image,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        try{
            $museum_image = Museum_Image::find($id);

        if (!$museum_image) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image not found',
            ], 404);
        }

        $request->validate([
            'museum_id' => 'required|integer',
            'image' => 'image',
            'title' => 'required|string',
            'description' => 'required|string',
            'is_featured' => 'required|boolean',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = time() . '.' . $image->extension();
            $image->move(public_path('images'), $image_name);
            $museum_image->image = $image_name;
        }

        $museum_image->museum_id = $request->museum_id;
        $museum_image->title = $request->title;
        $museum_image->description = $request->description;
        $museum_image->is_featured = $request->is_featured;
        $museum_image->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Image updated',
            'data' => $museum_image,
        ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update image',
                ], 500);
            }
        }
    
        public function destroy(Request $request, $id)
        {
            $museum_image = Museum_Image::find($id);
    
            if (!$museum_image) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Image not found',
                ], 404);
            }
    
            $museum_image->delete();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Image deleted',
            ]);
        }
    }
