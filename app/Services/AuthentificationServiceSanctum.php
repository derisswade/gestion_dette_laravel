<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Services\Interfaces\AuthentificationServiceInterface;

class AuthentificationServiceSanctum implements AuthentificationServiceInterface
{
    public function authentificate(array $credentials)
    {
        // Tentative de connexion avec les informations d'identification fournies
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'login' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        // $test = Auth::user();
        // Récupérer l'utilisateur avec le login fourni
        $user = User::where('login', $credentials['login'])->firstOrFail();

        // Créer un token Sanctum pour l'utilisateur
        $token = $user->createToken('auth_token')->plainTextToken;
        // generer un refresh token pour l'utilisateur et l'enregistrer dans la base de données
        $refesh_token = Str::random(60);
        $user->refresh_token = hash('sha256', $refesh_token);
        $user->save();
       
        // Retourner le token et l'utilisateur
        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'refresh_token' => $refesh_token,
            'user' => $user

        ];
    }

    public function logout($user)
    {
        // Supprimer tous les tokens associés à l'utilisateur
        $user->tokens()->delete();

        // Déconnecter l'utilisateur de la session actuelle
        Auth::logout();

        // Invalider la session actuelle
        request()->session()->invalidate();

        // Régénérer le jeton CSRF pour la session
        request()->session()->regenerateToken();
    }
}