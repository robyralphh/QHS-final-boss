<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ActionLogger;

class UserController extends Controller
{
    use ActionLogger;
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
    $query = User::query()->orderBy('id', 'desc');
    if ($request->has('role')) {
        $role = $request->query('role');
        $query->where('role', $role);
    }
    
    // Add pagination for better performance
    $perPage = $request->get('per_page', 50);
    return UserResource::collection($query->paginate($perPage));

    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        // Hash the password if it exists in the request
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        // Handle avatar file upload
        if ($request->hasFile('avatar')) {
            $avatarName = Str::random(32) . "." . $request->avatar->getClientOriginalExtension();
            $data['avatar'] = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        }
        
        $data['isActive'] = true;
        
        // Auto-verify email for users created by admin
        $data['email_verified_at'] = now();
        
        // Create the user
        $user = User::create($data);

        $this->logAction('user_created', ['user_id' => $user->id]);

        return response(new UserResource($user), 201);
    }

    /**
     * Display the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \App\Http\Resources\UserResource
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \App\Http\Resources\UserResource
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        // Hash the password if it exists in the request
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        // Handle avatar file upload
        if ($request->hasFile('avatar')) {
            $storage = Storage::disk('public');

            // Delete the old avatar if it exists
            if ($user->avatar) {
                $storage->delete($user->avatar);
            }

            // Store the new avatar
            $avatarName = Str::random(32) . "." . $request->avatar->getClientOriginalExtension();
            $data['avatar'] = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        }

        // Update the user
        $user->update($data);

        $this->logAction('user_updated', ['user_id' => $user->id]);

        return new UserResource($user);
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        // Delete the user's avatar if it exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Delete the user
        $user->delete();

        $this->logAction('user_deleted', ['user_id' => $user->id]);

        return response('', 204);
    }
}