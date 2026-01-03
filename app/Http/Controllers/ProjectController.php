<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Project;
use App\Models\Item;
use App\Models\ProjectResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::latest()->get()->map(function ($project) {
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

    /**
     * Attach inventory items to a project (liquidation) and deduct from stock.
     *
     * Expects payload:
     * [
     *   { "item_id": 2, "quantity": 5 },
     *   { "item_id": 1, "quantity": 1 }
     * ]
     */
    public function attachResources(Request $request, $projectId)
    {
        $payload = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $items = $payload['items'];

        $project = Project::findOrFail($projectId);

        try {
            DB::transaction(function () use ($items, $project) {
                foreach ($items as $resource) {
                    $item = Item::lockForUpdate()->findOrFail($resource['item_id']);

                    if ($item->quantity < $resource['quantity']) {
                        throw new \RuntimeException("Insufficient quantity for item {$item->id} ({$item->name}).");
                    }

                    $item->decrement('quantity', $resource['quantity']);

                    ProjectResource::create([
                        'project_id' => $project->id,
                        'item_id' => $item->id,
                        'quantity' => $resource['quantity'],
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Resources attached to project and inventory updated.',
        ], 201);
    }

    /**
     * List all resources for a project.
     */
    public function resources($projectId)
    {
        $project = Project::with(['resources.item'])->findOrFail($projectId);

        $resources = $project->resources->map(function ($resource) {
            return [
                'id' => $resource->id,
                'item_id' => $resource->item_id,
                'item_name' => optional($resource->item)->name,
                'quantity' => $resource->quantity,
                'created_at' => $resource->created_at,
                'updated_at' => $resource->updated_at,
            ];
        });

        return response()->json([
            'project_id' => $project->id,
            'resources' => $resources,
        ]);
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

    public function upcomingProjects(Request $request) {
        $search = $request->input('search');

        $projects = Project::whereDate('date', '>=', today())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%");
                });
            })
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
