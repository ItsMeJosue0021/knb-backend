<?php

namespace App\Http\Middleware;

use App\Models\AdminActivityLog;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AdminAuditMiddleware
{
    private const AUDIT_ROLES = ["admin", "super-admin"];
    private const AUDIT_HTTP_METHODS = ["POST", "PUT", "PATCH", "DELETE"];
    private const EDIT_VERBS = ["PUT", "PATCH"];
    private const CRITICAL_RESOURCE_METHODS = ["store", "update", "destroy"];

    private const CRITICAL_ACTION_OVERRIDES = [
        "App\\Http\\Controllers\\AuthController@register",
        "App\\Http\\Controllers\\AuthController@update",
        "App\\Http\\Controllers\\AuthController@updateRole",
        "App\\Http\\Controllers\\AuthController@destroy",
        "App\\Http\\Controllers\\AuthController@restore",
        "App\\Http\\Controllers\\MembershipRequestController@approve",
        "App\\Http\\Controllers\\MembershipRequestController@reject",
        "App\\Http\\Controllers\\VolunteerRequestController@approve",
        "App\\Http\\Controllers\\VolunteerRequestController@reject",
        "App\\Http\\Controllers\\HomepageInfoController@savePrograms",
        "App\\Http\\Controllers\\HomepageInfoController@saveEncouragement",
        "App\\Http\\Controllers\\HomepageInfoController@saveQuotes",
        "App\\Http\\Controllers\\HomepageInfoController@saveInvolvement",
        "App\\Http\\Controllers\\HomepageInfoController@saveCarouselImages",
        "App\\Http\\Controllers\\HomepageInfoController@deleteCarouselImage",
        "App\\Http\\Controllers\\CashDonationController@confirmCashDonation",
        "App\\Http\\Controllers\\CashDonationController@approve",
        "App\\Http\\Controllers\\GoodsDonationController@testConfirmation",
        "App\\Http\\Controllers\\GoodsDonationController@approve",
        "App\\Http\\Controllers\\GoodsDonationController@reject",
        "App\\Http\\Controllers\\GoodsDonationController@updateNameOrDescription",
        "App\\Http\\Controllers\\GoodsDonationController@store",
        "App\\Http\\Controllers\\GoodsDonationController@update",
        "App\\Http\\Controllers\\GoodsDonationController@destroy",
        "App\\Http\\Controllers\\CashLiquidationController@reconcileHistoryItems",
        "App\\Http\\Controllers\\CashLiquidationController@syncConfirmedItems",
        "App\\Http\\Controllers\\InventoryController@reconcileHistoryItems",
        "App\\Http\\Controllers\\InventoryController@syncConfirmedItems",
        "App\\Http\\Controllers\\ProjectController@attachResources",
        "App\\Http\\Controllers\\ProfileController@changePassword",
        "App\\Http\\Controllers\\ProfileController@uploadProfilePicture",
        "App\\Http\\Controllers\\EnquiryController@store",
        "App\\Http\\Controllers\\EnquiryController@update",
        "App\\Http\\Controllers\\EnquiryController@destroy",
    ];

    private const CRITICAL_RESOURCE_CONTROLLERS = [
        "App\\Http\\Controllers\\AuthController",
        "App\\Http\\Controllers\\RoleController",
        "App\\Http\\Controllers\\ContactInfoController",
        "App\\Http\\Controllers\\HomepageInfoController",
        "App\\Http\\Controllers\\WebsiteLogoController",
        "App\\Http\\Controllers\\KnowledgebaseController",
        "App\\Http\\Controllers\\OfficersController",
        "App\\Http\\Controllers\\FaqsController",
        "App\\Http\\Controllers\\ProjectController",
        "App\\Http\\Controllers\\EventController",
        "App\\Http\\Controllers\\VolunteerRequestController",
        "App\\Http\\Controllers\\MembershipRequestController",
        "App\\Http\\Controllers\\EnquiryController",
        "App\\Http\\Controllers\\CashDonationController",
        "App\\Http\\Controllers\\GoodsDonationController",
        "App\\Http\\Controllers\\ExpenditureController",
        "App\\Http\\Controllers\\ItemNameController",
        "App\\Http\\Controllers\\UnitController",
        "App\\Http\\Controllers\\GDCategoryController",
        "App\\Http\\Controllers\\GDSubcategoryController",
        "App\\Http\\Controllers\\ItemController",
        "App\\Http\\Controllers\\MemberController",
        "App\\Http\\Controllers\\EmergencyContactController",
        "App\\Http\\Controllers\\ProfileController",
        "App\\Http\\Controllers\\CashLiquidationController",
        "App\\Http\\Controllers\\InventoryController",
    ];

    private const RESOURCE_LABELS = [
        "RoleController" => "role",
        "AuthController" => "user",
        "MembershipRequestController" => "membership request",
        "VolunteerRequestController" => "volunteer request",
        "ContactInfoController" => "contact information",
        "HomepageInfoController" => "homepage content",
        "WebsiteLogoController" => "website logo",
        "KnowledgebaseController" => "knowledge base item",
        "OfficersController" => "officer",
        "FaqsController" => "FAQ",
        "ProjectController" => "project",
        "EventController" => "event",
        "EnquiryController" => "enquiry",
        "CashDonationController" => "cash donation",
        "GoodsDonationController" => "goods donation",
        "ExpenditureController" => "expenditure",
        "ItemNameController" => "item name",
        "UnitController" => "unit",
        "GDCategoryController" => "goods donation category",
        "GDSubcategoryController" => "goods donation subcategory",
        "ItemController" => "donation item",
        "MemberController" => "member",
        "EmergencyContactController" => "emergency contact",
        "ProfileController" => "user profile",
        "CashLiquidationController" => "cash liquidation",
        "InventoryController" => "inventory record",
    ];

    private const HIGH_SEVERITY_ACTIONS = [
        "App\\Http\\Controllers\\AuthController@register",
        "App\\Http\\Controllers\\AuthController@updateRole",
        "App\\Http\\Controllers\\AuthController@destroy",
        "App\\Http\\Controllers\\AuthController@restore",
        "App\\Http\\Controllers\\MembershipRequestController@approve",
        "App\\Http\\Controllers\\MembershipRequestController@reject",
        "App\\Http\\Controllers\\VolunteerRequestController@approve",
        "App\\Http\\Controllers\\VolunteerRequestController@reject",
        "App\\Http\\Controllers\\CashDonationController@approve",
        "App\\Http\\Controllers\\CashDonationController@confirmCashDonation",
        "App\\Http\\Controllers\\GoodsDonationController@testConfirmation",
        "App\\Http\\Controllers\\GoodsDonationController@approve",
        "App\\Http\\Controllers\\GoodsDonationController@reject",
        "App\\Http\\Controllers\\CashLiquidationController@reconcileHistoryItems",
        "App\\Http\\Controllers\\CashLiquidationController@syncConfirmedItems",
        "App\\Http\\Controllers\\InventoryController@reconcileHistoryItems",
        "App\\Http\\Controllers\\InventoryController@syncConfirmedItems",
        "App\\Http\\Controllers\\ProjectController@attachResources",
        "App\\Http\\Controllers\\ProjectController@destroy",
        "App\\Http\\Controllers\\GoodsDonationController@destroy",
        "App\\Http\\Controllers\\GoodsDonationController@testConfirmation",
        "App\\Http\\Controllers\\AuthController@update",
        "App\\Http\\Controllers\\AuthController@register",
    ];

    private const LOW_SEVERITY_ACTIONS = [
        "App\\Http\\Controllers\\ProfileController@uploadProfilePicture",
        "App\\Http\\Controllers\\HomepageInfoController@saveCarouselImages",
    ];

    private const ENTITY_MODEL_BY_CONTROLLER = [
        "App\\Http\\Controllers\\AuthController" => "App\\Models\\User",
        "App\\Http\\Controllers\\RoleController" => "App\\Models\\Role",
        "App\\Http\\Controllers\\MembershipRequestController" => "App\\Models\\MembershipRequest",
        "App\\Http\\Controllers\\VolunteerRequestController" => "App\\Models\\VolunteerRequest",
        "App\\Http\\Controllers\\ContactInfoController" => "App\\Models\\ContactInfo",
        "App\\Http\\Controllers\\HomepageInfoController" => "App\\Models\\HomepageInfo",
        "App\\Http\\Controllers\\WebsiteLogoController" => "App\\Models\\WebsiteLogo",
        "App\\Http\\Controllers\\KnowledgebaseController" => "App\\Models\\Knowledgebase",
        "App\\Http\\Controllers\\OfficersController" => "App\\Models\\Officers",
        "App\\Http\\Controllers\\FaqsController" => "App\\Models\\Faqs",
        "App\\Http\\Controllers\\ProjectController" => "App\\Models\\Project",
        "App\\Http\\Controllers\\EventController" => "App\\Models\\Event",
        "App\\Http\\Controllers\\EnquiryController" => "App\\Models\\Enquiry",
        "App\\Http\\Controllers\\CashDonationController" => "App\\Models\\CashDonation",
        "App\\Http\\Controllers\\GoodsDonationController" => "App\\Models\\GoodsDonation",
        "App\\Http\\Controllers\\ExpenditureController" => "App\\Models\\Expenditure",
        "App\\Http\\Controllers\\ItemNameController" => "App\\Models\\ItemName",
        "App\\Http\\Controllers\\UnitController" => "App\\Models\\Unit",
        "App\\Http\\Controllers\\GDCategoryController" => "App\\Models\\GDCategory",
        "App\\Http\\Controllers\\GDSubcategoryController" => "App\\Models\\GDSubcategory",
        "App\\Http\\Controllers\\ItemController" => "App\\Models\\Item",
        "App\\Http\\Controllers\\MemberController" => "App\\Models\\Member",
        "App\\Http\\Controllers\\EmergencyContactController" => "App\\Models\\EmergencyContact",
        "App\\Http\\Controllers\\ProfileController" => "App\\Models\\User",
        "App\\Http\\Controllers\\CashLiquidationController" => "App\\Models\\CashLiquidation",
        "App\\Http\\Controllers\\InventoryController" => "App\\Models\\InventoryItem",
    ];

    private const ENTITY_MODEL_BY_ACTION = [
        "App\\Http\\Controllers\\HomepageInfoController@saveEncouragement" => "App\\Models\\Encouragement",
        "App\\Http\\Controllers\\HomepageInfoController@savePrograms" => "App\\Models\\Programs",
        "App\\Http\\Controllers\\HomepageInfoController@saveQuotes" => "App\\Models\\Quotes",
        "App\\Http\\Controllers\\HomepageInfoController@saveInvolvement" => "App\\Models\\Involvement",
        "App\\Http\\Controllers\\HomepageInfoController@saveCarouselImages" => "App\\Models\\CarouselImage",
        "App\\Http\\Controllers\\HomepageInfoController@deleteCarouselImage" => "App\\Models\\CarouselImage",
        "App\\Http\\Controllers\\ProfileController@uploadProfilePicture" => "App\\Models\\User",
    ];

    private const ENTITY_SINGLETON_ACTIONS = [
        "App\\Http\\Controllers\\ContactInfoController@update",
        "App\\Http\\Controllers\\HomepageInfoController@update",
        "App\\Http\\Controllers\\HomepageInfoController@saveEncouragement",
        "App\\Http\\Controllers\\HomepageInfoController@savePrograms",
        "App\\Http\\Controllers\\HomepageInfoController@saveQuotes",
        "App\\Http\\Controllers\\HomepageInfoController@saveInvolvement",
        "App\\Http\\Controllers\\WebsiteLogoController@update",
    ];

    private const ENTITY_CHANGE_TRACKING_ACTIONS = [
        "App\\Http\\Controllers\\AuthController@update",
        "App\\Http\\Controllers\\AuthController@updateRole",
        "App\\Http\\Controllers\\AuthController@restore",
        "App\\Http\\Controllers\\AuthController@destroy",
        "App\\Http\\Controllers\\RoleController@update",
        "App\\Http\\Controllers\\RoleController@destroy",
        "App\\Http\\Controllers\\MembershipRequestController@approve",
        "App\\Http\\Controllers\\MembershipRequestController@reject",
        "App\\Http\\Controllers\\VolunteerRequestController@approve",
        "App\\Http\\Controllers\\VolunteerRequestController@reject",
        "App\\Http\\Controllers\\ContactInfoController@update",
        "App\\Http\\Controllers\\HomepageInfoController@update",
        "App\\Http\\Controllers\\HomepageInfoController@savePrograms",
        "App\\Http\\Controllers\\HomepageInfoController@saveEncouragement",
        "App\\Http\\Controllers\\HomepageInfoController@saveQuotes",
        "App\\Http\\Controllers\\HomepageInfoController@saveInvolvement",
        "App\\Http\\Controllers\\EnquiryController@update",
        "App\\Http\\Controllers\\EnquiryController@destroy",
        "App\\Http\\Controllers\\ProfileController@changePassword",
        "App\\Http\\Controllers\\ProfileController@uploadProfilePicture",
        "App\\Http\\Controllers\\ProjectController@attachResources",
        "App\\Http\\Controllers\\ProjectController@update",
        "App\\Http\\Controllers\\ProjectController@destroy",
        "App\\Http\\Controllers\\EventController@update",
        "App\\Http\\Controllers\\EventController@destroy",
        "App\\Http\\Controllers\\ItemController@store",
        "App\\Http\\Controllers\\ItemController@update",
        "App\\Http\\Controllers\\ItemController@destroy",
        "App\\Http\\Controllers\\GoodsDonationController@updateNameOrDescription",
        "App\\Http\\Controllers\\GoodsDonationController@update",
        "App\\Http\\Controllers\\GoodsDonationController@destroy",
        "App\\Http\\Controllers\\GoodsDonationController@testConfirmation",
        "App\\Http\\Controllers\\GoodsDonationController@approve",
        "App\\Http\\Controllers\\GoodsDonationController@reject",
        "App\\Http\\Controllers\\CashDonationController@approve",
        "App\\Http\\Controllers\\CashDonationController@confirmCashDonation",
        "App\\Http\\Controllers\\ExpenditureController@store",
        "App\\Http\\Controllers\\ExpenditureController@update",
        "App\\Http\\Controllers\\ExpenditureController@destroy",
        "App\\Http\\Controllers\\UnitController@store",
        "App\\Http\\Controllers\\UnitController@update",
        "App\\Http\\Controllers\\UnitController@destroy",
        "App\\Http\\Controllers\\KnowledgebaseController@update",
        "App\\Http\\Controllers\\KnowledgebaseController@destroy",
        "App\\Http\\Controllers\\FaqsController@update",
        "App\\Http\\Controllers\\FaqsController@destroy",
        "App\\Http\\Controllers\\OfficersController@update",
        "App\\Http\\Controllers\\OfficersController@destroy",
        "App\\Http\\Controllers\\CashLiquidationController@update",
        "App\\Http\\Controllers\\CashLiquidationController@destroy",
        "App\\Http\\Controllers\\InventoryController@reconcileHistoryItems",
        "App\\Http\\Controllers\\InventoryController@syncConfirmedItems",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $action = (string) optional($request->route())->getActionName();

        if (
            !$this->shouldAudit($request, $action) ||
            !$this->isCriticalAdminAction($action)
        ) {
            return $next($request);
        }

        $beforeSnapshot = $this->shouldTrackEntityChanges($request->method(), $action)
            ? $this->resolveBeforeSnapshot($request, $action)
            : null;

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $user = $this->resolveAuditUser($request);
            if (!$user || !$this->isAuditActor($user)) {
                throw $exception;
            }

            $this->recordAdminWrite(
                $user,
                $request,
                $action,
                $beforeSnapshot,
                null,
                null,
                null,
                $this->resolveFailureStatusCode($exception),
                $exception
            );

            throw $exception;
        }

        $user = $this->resolveAuditUser($request);
        if (!$user || !$this->isAuditActor($user)) {
            return $response;
        }

        $responseData = $this->extractResponseData($response);
        $afterSnapshot = $this->resolveAfterSnapshot($request, $action, $responseData);
        $diff = $this->buildEntityDiff($beforeSnapshot, $afterSnapshot);

        $this->recordAdminWrite(
            $user,
            $request,
            $action,
            $beforeSnapshot,
            $afterSnapshot,
            $diff,
            $responseData,
            method_exists($response, "getStatusCode") ? $response->getStatusCode() : null,
            null
        );

        return $response;
    }

    private function resolveAuditUser(Request $request)
    {
        return $request->user('sanctum') ?? $request->user();
    }

    private function isAuditActor(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = strtolower((string) optional($user->role)->name);
        return in_array($role, self::AUDIT_ROLES, true);
    }

    private function shouldAudit(Request $request, string $action): bool
    {
        if ($action === "" || Str::contains($action, "Closure")) {
            return false;
        }

        if (in_array($action, self::CRITICAL_ACTION_OVERRIDES, true)) {
            return true;
        }

        return in_array($request->method(), self::AUDIT_HTTP_METHODS, true);
    }

    private function isCriticalAdminAction(string $action): bool
    {
        if (in_array($action, self::CRITICAL_ACTION_OVERRIDES, true)) {
            return true;
        }

        $controller = (string) Str::beforeLast($action, "@");
        $method = (string) Str::afterLast($action, "@");

        if (
            in_array($controller, self::CRITICAL_RESOURCE_CONTROLLERS, true) &&
            in_array($method, self::CRITICAL_RESOURCE_METHODS, true)
        ) {
            return true;
        }

        return in_array($method, ["approve", "reject", "confirm", "restore"], true);
    }

    private function shouldTrackEntityChanges(string $httpMethod, string $action): bool
    {
        if (in_array($action, self::ENTITY_CHANGE_TRACKING_ACTIONS, true)) {
            return true;
        }

        return in_array($httpMethod, self::EDIT_VERBS, true);
    }

    private function recordAdminWrite(
        $user,
        Request $request,
        string $action,
        ?array $beforeSnapshot = null,
        ?array $afterSnapshot = null,
        ?array $diff = null,
        ?array $responseData = null,
        ?int $statusCode = null,
        ?Throwable $throwable = null
    ): void {
        $targetId = $this->resolveTargetId($request, $responseData, $beforeSnapshot);
        $metadata = [
            "payload" => $request->except([
                "password",
                "confirmPassword",
                "oldPassword",
                "newPassword",
                "confirm_password",
                "new_password",
                "token",
                "api_token",
            ]),
            "query" => $request->query(),
            "route_name" => optional($request->route())->getName(),
            "route_action" => $action,
            "target_id" => $targetId,
        ];

        if ($beforeSnapshot !== null || $afterSnapshot !== null) {
            $metadata["entity_changes"] = [
                "before" => $beforeSnapshot,
                "after" => $afterSnapshot,
                "diff" => $diff,
            ];
        }

        if ($throwable !== null) {
            $metadata["error"] = [
                "message" => $throwable->getMessage(),
                "type" => $throwable::class,
            ];
        }

        AdminActivityLog::create([
            "actor_id" => $user->id,
            "action" => $this->buildActionLabel($request, $action, $responseData),
            "method" => strtoupper($request->method()),
            "path" => $request->path(),
            "status_code" => $statusCode,
            "severity" => $this->resolveSeverity($request, $action),
            "metadata" => $metadata,
            "ip_address" => $request->ip(),
            "user_agent" => $request->userAgent(),
        ]);
    }

    private function resolveFailureStatusCode(Throwable $exception): ?int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof ValidationException) {
            return $exception->status;
        }

        if (method_exists($exception, "getStatusCode")) {
            return $exception->getStatusCode();
        }

        if (method_exists($exception, "getCode")) {
            $code = (int) $exception->getCode();
            return ($code >= 400 && $code <= 599) ? $code : 500;
        }

        return 500;
    }

    private function buildActionLabel(Request $request, string $action, ?array $responseData): string
    {
        $actionMethod = (string) Str::after($action, "@");
        $resource = $this->resolveResourceLabel($action);
        $resourceId = $this->resolveTargetId($request, $responseData, null);

        if ($action === "App\\Http\\Controllers\\AuthController@register") {
            return $this->appendTarget("Added a user account", $resourceId);
        }

        if ($action === "App\\Http\\Controllers\\AuthController@updateRole") {
            $roleName = $this->resolveRoleName((int) $request->input("role_id", 0));
            if ($roleName) {
                return $this->appendTarget("Assigned {$roleName} role to user", $resourceId);
            }

            return $this->appendTarget("Updated user role", $resourceId);
        }

        if ($action === "App\\Http\\Controllers\\AuthController@restore") {
            return $this->appendTarget("Restored user", $resourceId);
        }

        if (
            $action === "App\\Http\\Controllers\\HomepageInfoController@saveCarouselImages" ||
            $action === "App\\Http\\Controllers\\HomepageInfoController@deleteCarouselImage"
        ) {
            return $action === "App\\Http\\Controllers\\HomepageInfoController@deleteCarouselImage"
                ? $this->appendTarget("Deleted a homepage carousel image", $resourceId)
                : "Updated homepage carousel images.";
        }

        if (in_array($actionMethod, ["approve", "reject", "confirm"], true)) {
            return $this->appendTarget(ucfirst($actionMethod) . "ed {$resource}", $resourceId);
        }

        if ($actionMethod === "changePassword") {
            return $this->appendTarget("Changed user password", $resourceId);
        }

        if ($actionMethod === "uploadProfilePicture") {
            return $this->appendTarget("Updated user profile picture", $resourceId);
        }

        if ($actionMethod === "reconcileHistoryItems" || $actionMethod === "syncConfirmedItems") {
            return $this->appendTarget(ucfirst(Str::snake($actionMethod, " ")) . " {$resource}", $resourceId);
        }

        if (str_starts_with($actionMethod, "save")) {
            return $this->appendTarget("Updated {$resource}", $resourceId);
        }

        return $this->appendTarget($this->humanizeCrudAction($request->method(), $resource), $resourceId);
    }

    private function resolveSeverity(Request $request, string $action): string
    {
        if (in_array($action, self::HIGH_SEVERITY_ACTIONS, true)) {
            return "high";
        }

        if (in_array($action, self::LOW_SEVERITY_ACTIONS, true)) {
            return "low";
        }

        if (strtoupper($request->method()) === "DELETE") {
            return "high";
        }

        $controller = (string) Str::beforeLast($action, "@");
        return in_array($controller, [
            "App\\Http\\Controllers\\AuthController",
            "App\\Http\\Controllers\\MembershipRequestController",
            "App\\Http\\Controllers\\VolunteerRequestController",
            "App\\Http\\Controllers\\CashDonationController",
            "App\\Http\\Controllers\\GoodsDonationController",
            "App\\Http\\Controllers\\ProjectController",
            "App\\Http\\Controllers\\InventoryController",
            "App\\Http\\Controllers\\CashLiquidationController",
        ], true)
            ? "medium"
            : "low";
    }

    private function appendTarget(string $message, ?string $resourceId): string
    {
        if ($resourceId === null || $resourceId === "") {
            return "{$message}.";
        }

        return "{$message} with id [{$resourceId}].";
    }

    private function resolveResourceLabel(string $action): string
    {
        $controller = (string) Str::beforeLast($action, "@");
        $controllerName = (string) Str::afterLast($controller, "\\");
        return self::RESOURCE_LABELS[$controllerName] ?? "record";
    }

    private function humanizeCrudAction(string $method, string $resource): string
    {
        $verb = match ($method) {
            "POST" => "Added",
            "PUT" => "Updated",
            "PATCH" => "Updated",
            "DELETE" => "Deleted",
            default => ucfirst(strtolower($method)),
        };

        return "{$verb} {$resource}";
    }

    private function extractResponseData(Response $response): ?array
    {
        $content = method_exists($response, "getContent") ? $response->getContent() : null;
        if (!is_string($content) || trim($content) === "") {
            return null;
        }

        $payload = json_decode($content, true);
        return is_array($payload) ? $payload : null;
    }

    private function resolveTargetId(Request $request, ?array $responseData, ?array $beforeSnapshot): ?string
    {
        $routeParams = optional($request->route())->parameters() ?: [];
        $candidateKeys = [
            "id",
            "request_id",
            "requestId",
            "project_id",
            "projectId",
            "user_id",
            "userId",
            "member_id",
            "memberId",
            "event_id",
            "eventId",
            "donation_id",
            "donationId",
            "role_id",
            "roleId",
            "item_id",
            "itemId",
            "unit_id",
            "unitId",
        ];

        foreach ($candidateKeys as $key) {
            if (array_key_exists($key, $routeParams) && $routeParams[$key] !== null) {
                return (string) $routeParams[$key];
            }

            $candidate = optional($request->route())->parameter($key);
            if ($candidate !== null) {
                return (string) $candidate;
            }
        }

        if (is_array($responseData["data"] ?? null) && isset($responseData["data"]["id"])) {
            return (string) $responseData["data"]["id"];
        }

        if (isset($responseData["id"])) {
            return (string) $responseData["id"];
        }

        if ($beforeSnapshot && isset($beforeSnapshot["id"])) {
            return (string) $beforeSnapshot["id"];
        }

        return null;
    }

    private function resolveBeforeSnapshot(Request $request, string $action): ?array
    {
        $target = $this->resolveEntityTarget($request, $action, true);
        if ($target === null || $target["id"] === null) {
            if (!$target || empty($target["use_first"]) || empty($target["model"])) {
                return null;
            }

            $modelClass = $target["model"];
            return $this->normalizeSnapshot((new $modelClass)->latest("created_at")->first());
        }

        $modelClass = $target["model"];
        return $this->normalizeSnapshot((new $modelClass)->find($target["id"]));
    }

    private function resolveAfterSnapshot(Request $request, string $action, ?array $responseData): ?array
    {
        $target = $this->resolveEntityTarget($request, $action, false);
        if (!$target || ($target["id"] === null && !($target["use_first"] ?? false))) {
            return null;
        }

        if ($target["id"] !== null && !empty($target["model"])) {
            $modelClass = $target["model"];
            return $this->normalizeSnapshot((new $modelClass)->find($target["id"]));
        }

        if (!empty($target["use_first"]) && !empty($target["model"])) {
            $modelClass = $target["model"];
            return $this->normalizeSnapshot((new $modelClass)->latest("created_at")->first());
        }

        if (is_array($responseData["data"] ?? null)) {
            $recordId = $this->extractIdFromResponsePayload($responseData["data"]);
            if ($recordId !== null && isset(self::ENTITY_MODEL_BY_ACTION[$action])) {
                $modelClass = self::ENTITY_MODEL_BY_ACTION[$action];
                return $this->normalizeSnapshot((new $modelClass)->find($recordId));
            }
        }

        if (is_array($responseData) && isset($responseData["id"]) && isset(self::ENTITY_MODEL_BY_ACTION[$action])) {
            $modelClass = self::ENTITY_MODEL_BY_ACTION[$action];
            return $this->normalizeSnapshot((new $modelClass)->find($responseData["id"]));
        }

        return null;
    }

    private function extractIdFromResponsePayload(array $payload): ?string
    {
        if (isset($payload["id"])) {
            return (string) $payload["id"];
        }

        if (count($payload) === 1 && isset($payload[0]["id"])) {
            return (string) $payload[0]["id"];
        }

        return null;
    }

    private function buildEntityDiff(?array $beforeState, ?array $afterState): ?array
    {
        if (!is_array($beforeState) || !is_array($afterState)) {
            return null;
        }

        $keys = array_unique(array_merge(array_keys($beforeState), array_keys($afterState)));
        $diff = [];
        foreach ($keys as $key) {
            $beforeValue = $beforeState[$key] ?? null;
            $afterValue = $afterState[$key] ?? null;

            if ($beforeValue === $afterValue) {
                continue;
            }

            $diff[$key] = [
                "before" => $beforeValue,
                "after" => $afterValue,
            ];
        }

        return $diff;
    }

    private function normalizeSnapshot(mixed $model): ?array
    {
        if (!$model) {
            return null;
        }

        $snapshot = $model->toArray();
        unset($snapshot["password"], $snapshot["remember_token"]);
        return $snapshot;
    }

    private function resolveEntityTarget(Request $request, string $action, bool $before): ?array
    {
        $modelClass = self::ENTITY_MODEL_BY_ACTION[$action] ?? self::ENTITY_MODEL_BY_CONTROLLER[Str::beforeLast($action, "@")] ?? null;
        if (!$modelClass) {
            return null;
        }

        if (in_array($action, self::ENTITY_SINGLETON_ACTIONS, true)) {
            return [
                "model" => $modelClass,
                "id" => null,
                "use_first" => true,
            ];
        }

        $routeParams = optional($request->route())->parameters() ?: [];
        foreach (array_values($routeParams) as $value) {
            if (is_numeric($value)) {
                return [
                    "model" => $modelClass,
                    "id" => (string) $value,
                    "use_first" => false,
                ];
            }
        }

        if ($before && isset($routeParams["id"]) && is_array($request->request->all()) && isset($request->request->all()["id"])) {
            return [
                "model" => $modelClass,
                "id" => (string) $request->request->get("id"),
                "use_first" => false,
            ];
        }

        return [
            "model" => $modelClass,
            "id" => null,
            "use_first" => false,
        ];
    }

    private function resolveRoleName(int $roleId): ?string
    {
        if ($roleId <= 0) {
            return null;
        }

        return Role::find($roleId)?->name;
    }
}
