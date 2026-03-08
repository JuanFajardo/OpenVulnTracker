<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Solo administradores pueden gestionar tokens.');
    }

    public function index(): View
    {
        $this->authorizeAdmin();
        $tokens = ApiToken::with('creator')->orderByDesc('created_at')->get();
        return view('auth.tokens.index', compact('tokens'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'duration_days' => 'required|integer|min:1|max:3650',
        ]);

        ['plain' => $plain, 'hash' => $hash] = ApiToken::generate();

        ApiToken::create([
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'token_hash'    => $hash,
            'duration_days' => (int) $data['duration_days'],
            'expires_at'    => now()->addDays((int) $data['duration_days']),
            'created_by'    => auth()->id(),
        ]);

        return redirect()->route('tokens.index')
            ->with('new_token', $plain)
            ->with('success', 'Token creado exitosamente. Cópialo ahora, no se mostrará de nuevo.');
    }

    public function update(Request $request, ApiToken $token): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'duration_days' => 'required|integer|min:1|max:3650',
        ]);

        $token->update([
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'duration_days' => (int) $data['duration_days'],
            'expires_at'    => now()->addDays((int) $data['duration_days']),
        ]);

        return redirect()->route('tokens.index')
            ->with('success', 'Token actualizado. Nueva expiración: ' . now()->addDays((int) $data['duration_days'])->format('d/m/Y'));
    }

    public function destroy(ApiToken $token): RedirectResponse
    {
        $this->authorizeAdmin();
        $name = $token->name;
        $token->delete();

        return redirect()->route('tokens.index')
            ->with('success', "Token «{$name}» revocado y eliminado.");
    }
}
