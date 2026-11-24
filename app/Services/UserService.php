<?php

namespace App\Services;

use App\DTOs\UserData;
use App\Models\FinanceTransaction;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(
        protected ?AuditService $auditService = null
    ) {
        $this->auditService ??= app(AuditService::class);
    }

    public function createUser(UserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'username' => $data->username,
                'email' => $data->email,
                'password' => Hash::make((string) $data->password),
            ]);

            if ($data->role && Role::where('name', $data->role)->exists()) {
                $user->syncRoles([$data->role]);
            }

            $this->auditService->log(
                action: 'user.created',
                entity: $user,
                after: [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $data->role,
                ]
            );

            Cache::forget('users_list_all');

            return $user;
        });
    }

    public function updateUser(User $user, UserData $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $before = [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values()->all(),
            ];

            $updateData = [
                'name' => $data->name,
                'username' => $data->username,
                'email' => $data->email,
            ];

            if ($data->password) {
                $updateData['password'] = Hash::make($data->password);
            }

            $user->update($updateData);

            if ($data->role && Role::where('name', $data->role)->exists()) {
                $user->syncRoles([$data->role]);
            }

            $this->auditService->log(
                action: 'user.updated',
                entity: $user,
                before: $before,
                after: [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values()->all(),
                ]
            );

            Cache::forget('users_list_all');

            return $user->fresh();
        });
    }

    public function deleteUser(User $user): void
    {
        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages(['user' => 'You cannot delete your own account.']);
        }

        if ($user->sales()->exists()) {
            throw ValidationException::withMessages(['user' => 'Cannot delete user who has recorded sales.']);
        }

        if (Purchase::where('created_by', $user->id)->exists()) {
            throw ValidationException::withMessages(['user' => 'Cannot delete user who has recorded purchases.']);
        }

        if (FinanceTransaction::where('created_by', $user->id)->exists()) {
            throw ValidationException::withMessages(['user' => 'Cannot delete user who has recorded finance transactions.']);
        }

        $this->auditService->log(
            action: 'user.deleted',
            entity: $user,
            before: [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values()->all(),
            ]
        );

        $user->delete();

        Cache::forget('users_list_all');
    }
}
