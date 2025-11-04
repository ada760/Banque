<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Use policy to authorize viewing any accounts
        Gate::authorize('viewAny', Compte::class);

        $user = $request->user();

        if ($user->admin) {
            // Admin can see all accounts
            $comptes = Compte::all();
        } else {
            // Client can only see their own accounts
            $comptes = Compte::where('client_id', $user->client->id)->get();
        }

        return response()->json($comptes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Use policy to authorize creating accounts
        Gate::authorize('create', Compte::class);

        $validated = $request->validate([
            'num_compte' => 'required|string|unique:comptes',
            'devise' => 'required|string',
            'type' => 'required|in:cheque,epargne',
        ]);

        $validated['client_id'] = $request->user()->client->id;
        $validated['status'] = 'active';

        $compte = Compte::create($validated);

        return response()->json($compte, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Compte $compte)
    {
        // Use policy to authorize viewing this specific account
        Gate::authorize('view', $compte);

        return response()->json($compte);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Compte $compte)
    {
        // Use policy to authorize updating this account
        Gate::authorize('update', $compte);

        $validated = $request->validate([
            'num_compte' => 'sometimes|string|unique:comptes,num_compte,' . $compte->id,
            'devise' => 'sometimes|string',
            'type' => 'sometimes|in:cheque,epargne',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $compte->update($validated);

        return response()->json($compte);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Compte $compte)
    {
        // Use policy to authorize deleting this account
        Gate::authorize('delete', $compte);

        $compte->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }
}
