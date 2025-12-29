<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMembershipRequest;
use App\Http\Controllers\Controller;
use App\Services\MembershipService;
use Illuminate\Http\Request;

class MembershipRequestController extends Controller
{
    protected MembershipService $membershipService;

    public function __construct(MembershipService $membershipService)
    {
        $this->membershipService = $membershipService;
    }


    /**
     * List membership requests with optional search.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->input('serach', $request->input('search'));

        return response()->json([
            'requests' => $this->membershipService->getAll($search),
        ]);
    }

    /**
     * Get the current user's membership request if it exists.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myRequest(Request $request)
    {
        $membershipRequest = $this->membershipService->getByUser($request->user()->id);

        return response()->json([
            'message' => $membershipRequest ? 'Membership request found.' : 'No membership request found.',
            'request' => $membershipRequest,
        ], $membershipRequest ? 200 : 404);
    }

    /**
     * Create a new membership request.
     * @param SaveMembershipRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SaveMembershipRequest $request)
    {
        $membershipRequest = $this->membershipService->create($request->validated());

        return response()->json([
            'message' => 'Membership request submitted successfully.',
            'request' => $membershipRequest,
        ], 201);
    }

    /**
     * Approve a membership request.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(int $id)
    {
        $membershipRequest = $this->membershipService->approve($id);

        return response()->json([
            'message' => 'Membership request approved.',
            'request' => $membershipRequest,
        ]);
    }

    /**
     * Reject a membership request.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(int $id)
    {
        $membershipRequest = $this->membershipService->reject($id);

        return response()->json([
            'message' => 'Membership request rejected.',
            'request' => $membershipRequest,
        ]);
    }
}
