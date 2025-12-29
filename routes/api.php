<?php

use App\Http\Controllers\VolunteerRequestController;
use App\Models\VolunteerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AdvocacyController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\GDCategoryController;
use App\Http\Controllers\MembershipRequestController;
use App\Http\Controllers\ExpenditureController;
use App\Http\Controllers\CashDonationController;
use App\Http\Controllers\GCashDonationController;
use App\Http\Controllers\GDSubcategoryController;
use App\Http\Controllers\GoodsDonationController;
use App\Http\Controllers\KnowledgebaseController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ExpenditureItemController;
use App\Http\Controllers\EmergencyContactController;

Route::apiResource('roles', RoleController::class)->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::get('users', [AuthController::class, 'users'])->middleware('auth:sanctum');
Route::get('/users/print', [AuthController::class, 'printUsers'])->middleware('auth:sanctum');
Route::put('/users/{id}', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/users/{id}', [AuthController::class, 'destroy'])->middleware('auth:sanctum');

Route::post('/archived-users/restore/{id}', [AuthController::class, 'restore'])->middleware('auth:sanctum');
Route::get('/archived-users', [AuthController::class, 'archivedUsers'])->middleware('auth:sanctum');

Route::post('/users/change-password/{id}', [ProfileController::class, 'changePassword'])->middleware('auth:sanctum');
Route::post('/users/profile-update/{id}', [ProfileController::class, 'update'])->middleware('auth:sanctum');
Route::post('/users/profile-picture/{id}', [ProfileController::class, 'uploadProfilePicture'])->middleware('auth:sanctum');

Route::get('/membership-requests', [MembershipRequestController::class, 'index'])->middleware('auth:sanctum');
Route::get('/membership-requests/me', [MembershipRequestController::class, 'myRequest'])->middleware('auth:sanctum');
Route::post('/membership-requests', [MembershipRequestController::class, 'store'])->middleware('auth:sanctum');
Route::post('/membership-requests/{id}/approve', [MembershipRequestController::class, 'approve'])->middleware('auth:sanctum');
Route::post('/membership-requests/{id}/reject', [MembershipRequestController::class, 'reject'])->middleware('auth:sanctum');


Route::post('chat', [ChatBotController::class, 'chat']);

Route::post('/knowledgebase', [KnowledgebaseController::class, 'store'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('/knowledgebase', [KnowledgebaseController::class, 'getAll'])->middleware(['auth:sanctum', 'role:admin']);
Route::put('/knowledgebase/{id}', [KnowledgebaseController::class, 'update'])->middleware(['auth:sanctum', 'role:admin']);
Route::delete('/knowledgebase/{id}', [KnowledgebaseController::class, 'destroy'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('/knowledgebase/search', [KnowledgebaseController::class, 'search']);

Route::get('members/search', [MemberController::class, 'search'])->middleware(['auth:sanctum', 'role:admin']);
Route::apiResource('members', MemberController::class)->middleware(['auth:sanctum', 'role:admin']);

Route::apiResource('emergency-contacts', EmergencyContactController::class)->middleware(['auth:sanctum', 'role:admin']);

Route::get('/enquiries', [EnquiryController::class, 'index'])->middleware(['auth:sanctum', 'role:admin']);   // Get all
Route::post('/enquiries', [EnquiryController::class, 'store']); // Create
Route::put('/enquiries/{id}', [EnquiryController::class, 'update'])->middleware(['auth:sanctum', 'role:admin']);  // Update
Route::delete('/enquiries/{id}', [EnquiryController::class, 'destroy'])->middleware(['auth:sanctum', 'role:admin']);  // Delete
Route::get('/enquiries/search', [EnquiryController::class, 'search'])->middleware(['auth:sanctum', 'role:admin']);


// Projects
Route::get('/projects/search', [ProjectController::class, 'search']);
Route::apiResource('projects', ProjectController::class);
Route::post('/projects/update/{id}', [ProjectController::class, 'update']);
Route::get('/upcoming-projects', [ProjectController::class, 'upcomingProjects']);
Route::get('/past-projects', [ProjectController::class, 'pastProjects']);

Route::post('/projects/{id}/liquidate', [ProjectController::class, 'attachResources']);
Route::get('/projects/{id}/resources', [ProjectController::class, 'resources']);

// Volunterring request
Route::get('/volunteering-requests/', [VolunteerRequestController::class, 'index']);
Route::get('/volunteering-requests/{requestId}/approve', [VolunteerRequestController::class, 'approve']);
Route::get('/volunteering-requests/{requestId}/reject', [VolunteerRequestController::class, 'reject']);
Route::get('/volunteering-requests/by-user/{userId}', [VolunteerRequestController::class, 'getRequestsByUserId']);
Route::get('/volunteering-requests/by-project/{projectId}', [VolunteerRequestController::class, 'getApprovedByProjectId']);
Route::post('/projects/{project_id}/volunteer', [VolunteerRequestController::class, 'volunteer']);
Route::delete('/volunteering-requests/{project_id}', [VolunteerRequestController::class, 'destroy']);




Route::apiResource('events', EventController::class);
Route::post('/events/update/{id}', [EventController::class, 'update']);

Route::apiResource('advocacies', AdvocacyController::class);
Route::apiResource('donations', DonationController::class);
Route::get('/dashboard/donations/summary', [DonationController::class, 'getDonationSummary']);


// REPORTS
Route::get('/reports/cash-donations', [ReportController::class, 'CashDonations']);
Route::get('/reports/goods-donations', [ReportController::class, 'GoodsDonations']);

Route::post('/send-email', [EmailController::class, 'send']);
Route::get('/template', [EmailController::class, 'template']);

Route::get('/test-email', function () {
    Mail::raw('This is a test email from Kalinga', function ($message) {
        $message->to('joshuasalceda0021@gmail.com')
            ->subject('Test Email from Kalinga');
    });

    return 'Email sent!';
});

Route::post('/payments/gcash', [PaymentController::class, 'createGCashPayment']);

Route::post('/donations/cash/save', [CashDonationController::class, 'store']);
Route::post('/donations/cash/{id}/confirm', [CashDonationController::class, 'confirmCashDonation']);
Route::post('/donations/gcash/save', [GCashDonationController::class, 'store']);

Route::post('/donations/gcash/webhook', [PaymentWebhookController::class, 'handle']);

Route::get('/gcash-donations', [GCashDonationController::class, 'index']);
Route::get('/gcash-donations/filter', [GCashDonationController::class, 'filter']);
Route::get('/gcash-donations/search', [GCashDonationController::class, 'search']);
Route::get('/gcash-donations/stats', [GCashDonationController::class, 'stats']);
Route::get('/gcash-donations/counts', [GCashDonationController::class, 'counts']);
Route::get('/gcash-donations/print', [GCashDonationController::class, 'gcashDonations']);

Route::get('/cash-donations', [CashDonationController::class, 'index']);
Route::get('/cash-donations/filter', [CashDonationController::class, 'filter']);
Route::get('/cash-donations/search', [CashDonationController::class, 'search']);
Route::get('/cash-donations/stats', [CashDonationController::class, 'stats']);
Route::get('/cash-donations/counts', [CashDonationController::class, 'counts']);
Route::put('/cash-donations/{id}/approve', [CashDonationController::class, 'approve']);
Route::get('/cash-donations/v2/print', [CashDonationController::class, 'cashDonations']);
Route::put('/cash-donations/v2/{id}/approve', [CashDonationController::class, 'approve']);



Route::apiResource('goods-donations', GoodsDonationController::class);
Route::post('/goods-donations/update/{id}', [GoodsDonationController::class, 'update']);

// Goods Donation
Route::get('/goods-donations/v2', [GoodsDonationController::class, 'all']);
Route::get('/goods-donations/v2/filter', [GoodsDonationController::class, 'filter']);
Route::get('/goods-donations/v2/search', [GoodsDonationController::class, 'search']);
Route::get('/goods-donations/v2/stats', [GoodsDonationController::class, 'stats']);
Route::get('/goods-donations/v2/counts', [GoodsDonationController::class, 'counts']);
Route::get('/goods-donations/v2/print', [GoodsDonationController::class, 'goodsDonations']);
Route::put('/goods-donations/v2/{id}/approve', [GoodsDonationController::class, 'testConfirmation']);


// Get all Goods Donation Items by donation ID
Route::get('/goods-donations/{id}/items', [ItemController::class, 'index']);
// Get donation item by ID
Route::get('/goods-donations/items/{id}', [ItemController::class, 'show']);
// Save donation items
Route::post('/goods-donations/{id}/items', [ItemController::class, 'store']);
// Update donation item by id
Route::put('/goods-donations/items/{id}', [ItemController::class, 'update']);
// Delete donation item by id
Route::delete('/goods-donations/items/{id}', [ItemController::class, 'destroy']);

Route::get('/items', [ItemController::class, 'getAllItems']);


// Goods Donation Categories and Subcategories
Route::get('/goods-donation-categories', [GDCategoryController::class, 'index']);
// Goods Donation Subcategories
Route::get('/goods-donation-subcategories', [GDSubcategoryController::class, 'index']);



// Expenditures API routes
Route::get('/expenditures/search', [ExpenditureController::class, 'search']);
Route::get('/expenditures/date-range', [ExpenditureController::class, 'getByDateRange']);
Route::get('/expenditures/totals', [ExpenditureController::class, 'getTotals']);
Route::post('/expenditures/update/{id}', [ExpenditureController::class, 'update']);
Route::apiResource('expenditures', ExpenditureController::class);

// Expenditures Items API routes
Route::apiResource('expenditure-items', ExpenditureItemController::class);
