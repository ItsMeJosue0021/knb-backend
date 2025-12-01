<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ExpenditureItemService;
use App\Http\Requests\CreateExpItemRequest;

class ExpenditureItemController extends Controller
{

    private $expenditureItemService;

    public function __construct(ExpenditureItemService $expenditureItemService) {
        $this->expenditureItemService = $expenditureItemService;
    }

    /**
     * Retrieve all expenditure items
     *
     * @return mixed
     */
    public function index() {
        return response([
            'expenditure_items' => $this->expenditureItemService->getAllExpenditureItems()
        ], 200);
    }

    /**
     * Retrieve a specific expenditure item by its id
     *
     * @param int $id
     * @return mixed
     */
    public function getExpenditureItemById(int $id) {
        return response([
            'expenditure_item' => $this->expenditureItemService->getExpenditureItemById($id)
        ], 200);
    }

    /**
     * Retrieve a specific expenditure item by its id
     *
     * @param int $expenditure_id
     * @return mixed
     */
    public function getExpenditureItemsByExpenditureId(int $expenditure_id) {
        return response([
            'expenditure_items' => $this->expenditureItemService->getExpenditureItemsByExpenditureId($expenditure_id)
        ], 200);
    }

    /**
     * Saves expenditure item record to database
     *
     * @param \App\Http\Requests\CreateExpItemRequest
     * @return mixed
     */
    public function store(CreateExpItemRequest $request) {
        try {
            $data = $request->validated();
            $this->expenditureItemService->saveExpenditureItem($data);
            return response(['message' => 'Expenditure Item has been recorded!'], 201);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Updates expenditure item record to database
     *
     * @param int $id
     * @param \App\Http\Requests\CreateExpItemRequest
     * @return mixed
     */
    public function update(int $id, CreateExpItemRequest $request) {
        try {
            $data = $request->validated();
            $this->expenditureItemService->updateExpenditureItem($id, $data);
            return response(['message' => 'Expenditure Item has been updated!'], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * Deletes expenditure item record from database
     *
     * @param int $id
     * @return mixed
     */
    public function destroy(int $id) {
        try {
            $this->expenditureItemService->deleteExpenditureItem($id);
            return response(['message' => 'Expenditure Item has been deleted!'], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
