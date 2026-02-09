<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserLockRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * List users
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('roles');

        // Filters
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('is_locked')) {
            $query->where('is_locked', $request->boolean('is_locked'));
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query->orderBy($sort, $order);

        // Pagination
        $limit = min($request->input('limit', 10), 100);
        $users = $query->paginate($limit);

        return \App\Http\Resources\UserResource::collection($users);
    }

    /**
     * Create user
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // Assign role
        $user->assignRole($data['role']);

        \App\Models\AuditLog::log('create_user', User::class, $user->id, [
            'role' => $data['role']
        ]);

        return (new \App\Http\Resources\UserResource($user->load('roles')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show user detail
     */
    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);

        return new \App\Http\Resources\UserResource($user->load('roles'));
    }

    /**
     * Update user
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        // Handle password
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Update role if changed
        $oldRole = $user->roles->first()?->name;
        if (isset($data['role']) && $data['role'] !== $oldRole) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        $user->update($data);

        \App\Models\AuditLog::log('update_user', User::class, $user->id);

        return new \App\Http\Resources\UserResource($user->fresh(['roles']));
    }

    /**
     * Lock user
     */
    public function lock(AdminUserLockRequest $request, User $user)
    {
        $this->authorize('lock', $user);

        DB::transaction(function () use ($request, $user): void {
            $user->update(['is_locked' => true]);

            // Revoke all tokens
            $user->tokens()->delete();

            \App\Models\AuditLog::log('lock_user', User::class, $user->id, [
                'reason' => $request->validated()['reason'],
                'duration' => $request->validated()['duration'] ?? 'permanent',
            ]);
        });

        return response()->json([
            'message' => 'User đã bị khóa.',
            'data' => new \App\Http\Resources\UserResource($user)
        ]);
    }

    /**
     * Unlock user
     */
    public function unlock(Request $request, User $user)
    {
        $this->authorize('lock', $user);

        DB::transaction(function () use ($user): void {
            $user->update(['is_locked' => false]);
            \App\Models\AuditLog::log('unlock_user', User::class, $user->id);
        });

        return response()->json([
            'message' => 'User đã được mở khóa.',
            'data' => new \App\Http\Resources\UserResource($user)
        ]);
    }

    /**
     * Soft delete user
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();

            \App\Models\AuditLog::log('delete_user', User::class, $user->id);
        });

        return response()->json([
            'message' => 'User đã được xóa mềm.',
        ]);
    }
}
