<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveVolunteeringRequest;
use App\Services\VolunteerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VolunteerRequestController extends Controller
{
    protected VolunteerService $volunteerService;

    public function __construct(VolunteerService $volunteerService) {
        $this->volunteerService = $volunteerService;
    }

    /**
     * Summary of index
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return response([
            'requests' => $this->volunteerService->getAllRequests()
        ], 200);
    }

    /**
     * Summary of volunter
     * @param SaveVolunteeringRequest $request
     * @param int $project_id
     * @return \Illuminate\Http\Response
     */
    public function volunteer(SaveVolunteeringRequest $request, int $project_id) {
        $data = $request->validated();
        try {
            $volunteerRequest = $this->volunteerService->volunteer($data, $project_id);

            return response([
                'volunteer_request' => $volunteerRequest,
                'message' => 'Volunteer request submitted successfully.',
            ], 201);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Summary of destroy
     * @param int $request_id
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $request_id) {
        try {
            $this->volunteerService->delete($request_id);
            return response([
                'message' => 'Request has been deleted successfully!'
            ], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Summary of getRequestByUserId
     * @param int $userId
     * @return \Illuminate\Http\Response
     */
    public function getRequestsByUserId (int $userId) {
        try {
            return response([
                'requests' => $this->volunteerService->getRequestsByUserId($userId)
            ], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Summary of getApprovedByProjectId
     * @param int $projectId
     * @return Response
     */
    public function getApprovedByProjectId(int $projectId) {
        try {
            return response([
                'volunteers' => $this->volunteerService->getApprovedByProjectId($projectId)
            ], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Summary of approve
     * @param int $requestId
     * @return \Illuminate\Http\Response
     */
    public function approve (int $requestId) {
        try {
            $this->volunteerService->approve($requestId);
            return response([
                'message' => 'The request has been approved.'
            ], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Summary of reject
     * @param int $requestId
     * @return \Illuminate\Http\Response
     */
    public function reject (int $requestId) {
        try {
            $this->volunteerService->reject($requestId);
            return response([
                'message' => 'The request has been rejected.'
            ], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

}
