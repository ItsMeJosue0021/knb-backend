<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Services\ItemService;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveItemsRequest;
use App\Http\Requests\UpdateItemRequest;

class ItemController extends Controller
{
    protected ItemService $itemService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
    }

    /**
     * Returns all items
     */
    public function getAllItems(Request $request)
    {
        return response([
            'items' => $this->itemService->getAllItems([
                'search' => $request->query('search', ''),
                'category' => $request->query('category', ''),
                'sub_category' => $request->query('sub_category', '')
            ])
        ], 200);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function index(int $id)
    {
        return response([
            'items' => $this->itemService->getItemsByDonationId($id)
        ], 200);
    }

    public function show(int $id)
    {
        return response([
            'item' => $this->itemService->getItemsById($id)
        ], 200);
    }

    /**
     * Saves items
     *
     * @param int
     * @param \App\Http\Requests\SaveItemsRequest
     * @return mixed
     */
    public function store(int $id, SaveItemsRequest $request)
    {
        try {
            $data = $request->validated();
            $this->itemService->saveItems($id, $data);
            return response(['message' => 'Items has been added!'], 201);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Updates an Item by its id
     *
     * @param int
     * @param \App\Http\Requests\UpdateItemRequest
     * @return mixed
     */
    public function update(int $id, UpdateItemRequest $request)
    {
        try {
            $data = $request->validated();
            $this->itemService->updateItem($id, $data);
            return response(['message' => 'Item has been updated!'], 200);
        } catch (Exception $exception) {
            return response(['message' => $exception->getMessage()], 500);
        }
    }

    /**
     * Deletes an Item by its id
     *
     * @param int
     * @return mixed
     */
    public function destroy(int $id)
    {
        try {
            $this->itemService->deleteItem($id);
            return response(['message' => 'Item has been deleted!'], 200);
        } catch (Exception $exception) {
            return response(['message' => $exception->getMessage()], 500);
        }
    }
}
