<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = new User($request->only(['firstname', 'lastname', 'email', 'password', 'birthdate']));

        if (! $user->isValid()) {
            return response()->json(['message' => 'Données utilisateur invalides.'], 422);
        }

        $user->save();

        return response()->json($user, 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        return response()->json($user, 200);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $user->fill($request->only(['firstname', 'lastname', 'email', 'password', 'birthdate']));

        if (! $user->isValid()) {
            return response()->json(['message' => 'Données utilisateur invalides.'], 422);
        }

        $user->save();

        return response()->json($user, 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}
