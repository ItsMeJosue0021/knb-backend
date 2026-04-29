<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Project;
use App\Models\Item;
use App\Models\ProjectProposedResource;
use App\Models\ProjectResource;
use App\Models\VolunteerRequest;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $projects = Project::query()
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            })
            ->latest()
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

                    $this->inventoryService->consumeFromProject(
                        $item,
                        (int) $resource['quantity'],
                        (int) $project->id
                    );

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
        $project = Project::with([
            'resources.item.categoryModel:id,name',
            'resources.item.subCategoryModel:id,name',
            'proposedResources.categoryModel:id,name',
            'proposedResources.subCategoryModel:id,name',
        ])->findOrFail($projectId);

        $resources = $project->resources->map(function ($resource) {
            return $this->formatActualResource($resource);
        })->values()->all();

        $proposedResources = $project->proposedResources->map(function ($resource) {
            return $this->formatProposedResource($resource);
        })->values()->all();

        return response()->json([
            'project_id' => $project->id,
            'resources' => $resources,
            'proposed_resources' => $proposedResources,
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
            'proposed_resources' => 'nullable|array',
            'proposed_resources.*.name' => 'required|string|max:255',
            'proposed_resources.*.category_id' => 'nullable|integer|exists:g_d_categories,id',
            'proposed_resources.*.sub_category_id' => 'nullable|integer|exists:g_d_subcategories,id',
            'proposed_resources.*.quantity' => 'required|integer|min:1',
            'proposed_resources.*.unit' => 'nullable|string|max:50',
            'proposed_resources.*.notes' => 'nullable|string',
        ]);


        try {
            $saved = null;
            DB::transaction(function () use ($request, $project, &$saved) {
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

                $this->syncProposedResources($saved, $project['proposed_resources'] ?? []);
            });

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
            'sync_proposed_resources' => 'sometimes|boolean',
            'proposed_resources' => 'nullable|array',
            'proposed_resources.*.name' => 'required|string|max:255',
            'proposed_resources.*.category_id' => 'nullable|integer|exists:g_d_categories,id',
            'proposed_resources.*.sub_category_id' => 'nullable|integer|exists:g_d_subcategories,id',
            'proposed_resources.*.quantity' => 'required|integer|min:1',
            'proposed_resources.*.unit' => 'nullable|string|max:50',
            'proposed_resources.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $project, &$data) {
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('projects', 'public');
            }

            $project->update(collect($data)->except('proposed_resources')->all());

            if (isset($data['tags'])) {
                $tagIds = collect($data['tags'])->map(function ($tagText) {
                    return Tag::firstOrCreate(['text' => $tagText])->id;
                });
                $project->tags()->sync($tagIds);
            }

            if ($request->boolean('sync_proposed_resources')) {
                $this->syncProposedResources($project, $data['proposed_resources'] ?? []);
            }
        });

        return response()->json(
            $this->formatProjectDetail(
                $project->fresh([
                    'tags',
                    'proposedResources.categoryModel:id,name',
                    'proposedResources.subCategoryModel:id,name',
                ])
            )
        );
    }

    public function show($id)
    {
        $project = Project::with([
            'tags',
            'proposedResources.categoryModel:id,name',
            'proposedResources.subCategoryModel:id,name',
        ])->findOrFail($id);

        return response()->json($this->formatProjectDetail($project));
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

    public function print(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $projects = Project::query()
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            })
            ->orderBy('date', 'asc')
            ->get();

        $projectIds = $projects->pluck('id');
        $volunteersByProject = VolunteerRequest::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'approved')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->groupBy('project_id')
            ->map(function ($requests) {
                return $requests->map(function ($request) {
                    $middle = $request->middle_name ? ' ' . $request->middle_name : '';
                    return trim($request->first_name . $middle . ' ' . $request->last_name);
                })->values()->all();
            });

        $pdf = Pdf::loadView('projects.report', [
            'projects' => $projects,
            'volunteersByProject' => $volunteersByProject,
            'generatedAt' => now(),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('projects.pdf');
    }

    public function printLiquidatedItems(int $projectId)
    {
        $project = Project::with([
            'resources.item.categoryModel:id,name',
            'resources.item.subCategoryModel:id,name',
            'proposedResources.categoryModel:id,name',
            'proposedResources.subCategoryModel:id,name',
        ])->findOrFail($projectId);

        $actualResources = $project->resources->map(function ($resource) {
            return $this->formatActualResource($resource);
        })->values()->all();

        $proposedResources = $project->proposedResources->map(function ($resource) {
            return $this->formatProposedResource($resource);
        })->values()->all();

        $pdf = Pdf::loadView('projects.liquidated-report', [
            'project' => $project,
            'resources' => $actualResources,
            'proposedResources' => $proposedResources,
            'comparisonRows' => $this->buildResourceComparisonRows($proposedResources, $actualResources),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeTitle = preg_replace('/[^A-Za-z0-9_-]+/', '-', $project->title);
        $fileName = 'project-liquidation-' . $project->id . '-' . $safeTitle . '.pdf';

        return $pdf->stream($fileName);
    }

    public function printFile($filename)
    {
        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            return response()->json(['message' => 'Invalid filename.'], 400);
        }

        $path = 'reports/' . $filename;
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $file = Storage::disk('public')->get($path);

        return response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function syncProposedResources(Project $project, array $resources): void
    {
        $project->proposedResources()->delete();

        foreach (array_values($resources) as $index => $resource) {
            $project->proposedResources()->create([
                'name' => trim((string) ($resource['name'] ?? '')),
                'category_id' => !empty($resource['category_id']) ? (int) $resource['category_id'] : null,
                'sub_category_id' => !empty($resource['sub_category_id']) ? (int) $resource['sub_category_id'] : null,
                'quantity' => (int) ($resource['quantity'] ?? 0),
                'unit' => isset($resource['unit']) ? trim((string) $resource['unit']) : null,
                'notes' => isset($resource['notes']) ? trim((string) $resource['notes']) : null,
                'display_order' => $index,
            ]);
        }
    }

    private function formatProjectDetail(Project $project): array
    {
        return [
            'id' => $project->id,
            'title' => $project->title,
            'date' => $project->date,
            'location' => $project->location,
            'description' => $project->description,
            'tags' => $project->tags ? explode(',', $project->tags) : [],
            'image' => $project->image,
            'is_event' => $project->is_event,
            'proposed_resources' => $project->proposedResources->map(function ($resource) {
                return $this->formatProposedResource($resource);
            })->values()->all(),
        ];
    }

    private function formatProposedResource(ProjectProposedResource $resource): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'category_id' => $resource->category_id,
            'category_name' => optional($resource->categoryModel)->name,
            'sub_category_id' => $resource->sub_category_id,
            'sub_category_name' => optional($resource->subCategoryModel)->name,
            'quantity' => $resource->quantity,
            'unit' => $resource->unit,
            'notes' => $resource->notes,
            'display_order' => $resource->display_order,
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
        ];
    }

    private function formatActualResource(ProjectResource $resource): array
    {
        $item = $resource->item;

        return [
            'id' => $resource->id,
            'project_resource_id' => $resource->id,
            'item_id' => $resource->item_id,
            'item_name' => optional($item)->name,
            'name' => optional($item)->name,
            'category_id' => optional($item)->category,
            'category_name' => optional(optional($item)->categoryModel)->name,
            'sub_category_id' => optional($item)->sub_category,
            'sub_category_name' => optional(optional($item)->subCategoryModel)->name,
            'quantity' => $resource->quantity,
            'used_quantity' => $resource->quantity,
            'unit' => optional($item)->unit,
            'notes' => optional($item)->notes,
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
        ];
    }

    private function buildResourceComparisonRows(array $proposedResources, array $actualResources): array
    {
        $rows = [];

        foreach ($proposedResources as $proposed) {
            $matchedActuals = array_values(array_filter($actualResources, function ($actual) use ($proposed) {
                return $this->resourceEntriesMatch($proposed, $actual);
            }));

            $actualQuantity = array_reduce($matchedActuals, function ($sum, $actual) {
                return $sum + (int) ($actual['quantity'] ?? $actual['used_quantity'] ?? 0);
            }, 0);

            $proposedQuantity = (int) ($proposed['quantity'] ?? 0);
            $excessQuantity = $actualQuantity > $proposedQuantity ? $actualQuantity - $proposedQuantity : 0;
            $status = $actualQuantity <= 0
                ? 'missing'
                : ($actualQuantity < $proposedQuantity
                    ? 'partial'
                    : ($actualQuantity > $proposedQuantity ? 'excess' : 'accomplished'));

            $rows[] = [
                'proposed' => $proposed,
                'actual' => $matchedActuals,
                'proposed_quantity' => $proposedQuantity,
                'actual_quantity' => $actualQuantity,
                'excess_quantity' => $excessQuantity,
                'status' => $status,
            ];
        }

        foreach ($actualResources as $actual) {
            $hasMatchingProposal = collect($proposedResources)->contains(function ($proposed) use ($actual) {
                return $this->resourceEntriesMatch($proposed, $actual);
            });

            if ($hasMatchingProposal) {
                continue;
            }

            $rows[] = [
                'proposed' => null,
                'actual' => [$actual],
                'proposed_quantity' => 0,
                'actual_quantity' => (int) ($actual['quantity'] ?? $actual['used_quantity'] ?? 0),
                'excess_quantity' => 0,
                'status' => 'unplanned',
            ];
        }

        return $rows;
    }

    private function resourceEntriesMatch(array $proposed, array $actual): bool
    {
        $proposedCategory = (string) ($proposed['category_id'] ?? '');
        $actualCategory = (string) ($actual['category_id'] ?? '');
        if ($proposedCategory !== '' && $actualCategory !== '' && $proposedCategory !== $actualCategory) {
            return false;
        }

        $proposedSubcategory = (string) ($proposed['sub_category_id'] ?? '');
        $actualSubcategory = (string) ($actual['sub_category_id'] ?? '');
        if ($proposedSubcategory !== '' && $actualSubcategory !== '' && $proposedSubcategory !== $actualSubcategory) {
            return false;
        }

        $proposedName = $this->normalizeResourceText((string) ($proposed['name'] ?? $proposed['item_name'] ?? ''));
        $actualName = $this->normalizeResourceText((string) ($actual['name'] ?? $actual['item_name'] ?? ''));

        if ($proposedName === '' || $actualName === '') {
            return true;
        }

        return $proposedName === $actualName
            || str_contains($proposedName, $actualName)
            || str_contains($actualName, $proposedName);
    }

    private function normalizeResourceText(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }
}
