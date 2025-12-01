<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::all()->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'date' => $project->date,
                'location' => $project->location,
                'description' => $project->description,
                'tags' => $project->tags ? explode(',', $project->tags) : [],
                'image' => $project->image,
                'is_event' => $project->is_event,
            ];
        });

        return response()->json($projects);
    }

    public function pastProjects() {
        $projects = Project::whereDate('date', '<', today())
            ->orderBy('date', 'desc')
            ->take(2)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'date' => $project->date,
                    'location' => $project->location,
                    'description' => $project->description,
                    'tags' => $project->tags ? explode(',', $project->tags) : [],
                    'image' => $project->image,
                    'is_event' => $project->is_event,
                ];
            });

        return response()->json($projects);
    }

    public function upcomingProjects() {
        $projects = Project::whereDate('date', '>=', today())
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'date' => $project->date,
                    'location' => $project->location,
                    'description' => $project->description,
                    'tags' => $project->tags ? explode(',', $project->tags) : [],
                    'image' => $project->image,
                    'is_event' => $project->is_event,
                ];
            });

        return response()->json($projects);
    }

    public function store(Request $request)
    {

        $project = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'image' => 'nullable|image|max:2048',
            'is_event' => 'sometimes|boolean',
        ]);


        try {
            $saved = Project::create([
                'title' => $project['title'],
                'date' => $project['date'],
                'location' => $project['location'],
                'description' => $project['description'],
                'tags' => isset($project['tags']) ? implode(',', $project['tags']) : null,
                'is_event' => $project['is_event'] ?? false,
            ]);

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('projects', 'public');
                $saved->image = $imagePath;
                $saved->save();
            }

            if (!empty($project['tags'])) {
                foreach ($project['tags'] as $tagText) {
                    Tag::create([
                        'text' => $tagText,
                        'project_id' => $saved->id,
                    ]);
                }
            }

            return response()->json(['message' => 'Projects saved successfully.'], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save projects.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'location' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'tags' => 'array',
            'tags.*' => 'string',
            'image' => 'nullable|image|max:2048',
            'is_event' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('projects', 'public');
        }

        $project->update($data);

        if (isset($data['tags'])) {
            $tagIds = collect($data['tags'])->map(function ($tagText) {
                return Tag::firstOrCreate(['text' => $tagText])->id;
            });
            $project->tags()->sync($tagIds);
        }

        return response()->json($project->load('tags'));
    }

    public function show($id)
    {
        $project = Project::with('tags')->findOrFail($id);
        return response()->json($project);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();
        return response()->json(['message' => 'Project deleted successfully.'], 200);
    }

    public function search(Request $request)
    {
        $query = $request->input('search');

        $projects = Project::where('title', 'like', "%$query%")
            ->orWhere('description', 'like', "%$query%")
            ->orWhere('location', 'like', "%$query%")
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'date' => $project->date,
                    'location' => $project->location,
                    'description' => $project->description,
                    'tags' => $project->tags ? explode(',', $project->tags) : [],
                    'image' => $project->image,
                    'is_event' => $project->is_event,
                ];
            });

        return response()->json($projects);
    }
}
