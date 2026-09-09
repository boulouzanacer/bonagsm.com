<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AuthChangePasswordRequest;
use App\Http\Requests\Api\V1\AuthLoginRequest;
use App\Http\Requests\Api\V1\AuthRegisterRequest;
use App\Http\Requests\Api\V1\AuthUpdateProfileRequest;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function register(AuthRegisterRequest $request)
    {
        $data = $request->validated();

        $idWilaya = isset($data['id_wilaya'])
            ? (int) $data['id_wilaya']
            : (int) (DB::table('wilaya')->min('ID_WILAYA') ?? 1);

        $idCommune = isset($data['id_commune'])
            ? (int) $data['id_commune']
            : (int) (DB::table('commune')->where('ID_WILAYA', $idWilaya)->min('ID_COMMUNE') ?? 1);

        $existing = Client::query()->where('email', $data['email'])->first();

        if ($existing && ! empty($existing->email_verified_at)) {
            return $this->error('Email déjà utilisé', null, 422);
        }

        if ($existing && $existing->type_client !== 'simple') {
            return $this->error('Email déjà utilisé', null, 422);
        }

        $frs = Fournisseur::single();
        $frsId = (int) ($frs?->id ?? 0) ?: null;
        $needsAdminApproval = $frs && (int) $frs->admin_approval_required === 1;

        $payload = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'] ?? '',
            'id_wilaya' => $idWilaya,
            'id_commune' => $idCommune,
            'type_client' => 'simple',
            'id_frs' => $frsId,
            'actif' => 1,
            'approved_by_admin' => $needsAdminApproval ? 0 : 1,
            'email_verified_at' => null,
        ];

        $client = $existing
            ? tap($existing)->update($payload)
            : Client::create($payload);

        if (! $this->sendEmailVerificationCode($client)) {
            return $this->error('Impossible d’envoyer le code', null, 500);
        }

        return $this->success([
            'verification_required' => true,
            'email' => $client->email,
            'expires_in_minutes' => 10,
        ], 'Code envoyé. Vérifiez votre email.');
    }

    public function login(AuthLoginRequest $request)
    {
        $data = $request->validated();

        $client = Client::query()->where('email', $data['email'])->first();

        if (! $client || ! Hash::check($data['password'], $client->password)) {
            return $this->error('Identifiants invalides', null, 401);
        }

        if ((int) $client->actif !== 1) {
            return $this->error('Compte désactivé', null, 403);
        }

        if ($client->type_client === 'simple' && empty($client->email_verified_at)) {
            return $this->error('Email non vérifié', null, 403);
        }

        $frs = $client->fournisseur ?? Fournisseur::single();
        if ($frs && (int) $frs->admin_approval_required === 1 && (int) $client->approved_by_admin !== 1) {
            return $this->error("Compte en attente d'approbation par l'administrateur", [
                'approval_pending' => true,
            ], 403);
        }

        $token = $client->createToken('client')->plainTextToken;

        $fournisseur = null;
        if ($client->id_frs) {
            $frs = Fournisseur::query()->find($client->id_frs);
            if ($frs) {
                $fournisseur = [
                    'id' => $frs->id,
                    'nom_frs' => $frs->nom_frs,
                ];
            }
        }

        return $this->success([
            'token' => $token,
            'client' => [
                'id' => $client->id,
                'nom' => $client->nom,
                'prenom' => $client->prenom,
                'email' => $client->email,
                'telephone' => $client->telephone,
                'adresse' => $client->adresse,
                'id_wilaya' => $client->id_wilaya,
                'id_commune' => $client->id_commune,
                'type_client' => $client->type_client,
                'tarif' => (int) ($client->tarif ?? 1),
                'id_frs' => $client->id_frs,
                'fournisseur' => $fournisseur,
            ],
        ], 'Connexion réussie');
    }

    public function verifyEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $client = Client::query()->where('email', $data['email'])->first();
        if (! $client) {
            return $this->notFound();
        }

        if ($client->type_client !== 'simple') {
            return $this->error('Non autorisé', null, 403);
        }

        $frs = $client->fournisseur ?? Fournisseur::single();
        $approvalRequired = $frs && (int) $frs->admin_approval_required === 1 && (int) $client->approved_by_admin !== 1;

        if (! empty($client->email_verified_at)) {
            if ($approvalRequired) {
                return $this->success([
                    'approval_pending' => true,
                    'email' => $client->email,
                ], "Email déjà vérifié. Compte en attente d'approbation");
            }
            $token = $client->createToken('client')->plainTextToken;
            return $this->success([
                'token' => $token,
                'client' => [
                    'id' => $client->id,
                    'nom' => $client->nom,
                    'prenom' => $client->prenom,
                    'email' => $client->email,
                    'telephone' => $client->telephone,
                    'type_client' => $client->type_client,
                    'tarif' => (int) ($client->tarif ?? 1),
                    'id_frs' => $client->id_frs,
                ],
            ], 'Email déjà vérifié');
        }

        if (empty($client->email_verification_code_hash) || empty($client->email_verification_expires_at)) {
            return $this->error('Code invalide', null, 422);
        }

        $expiresAt = Carbon::parse($client->email_verification_expires_at);
        if (Carbon::now()->greaterThan($expiresAt)) {
            return $this->error('Code expiré', null, 422);
        }

        if (! Hash::check($data['code'], $client->email_verification_code_hash)) {
            return $this->error('Code incorrect', null, 422);
        }

        $client->forceFill([
            'email_verified_at' => Carbon::now(),
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
        ])->save();

        if ($approvalRequired) {
            return $this->success([
                'approval_pending' => true,
                'email' => $client->email,
            ], "Email vérifié. Compte en attente d'approbation par l'administrateur");
        }

        $token = $client->createToken('client')->plainTextToken;

        return $this->success([
            'token' => $token,
            'client' => [
                'id' => $client->id,
                'nom' => $client->nom,
                'prenom' => $client->prenom,
                'email' => $client->email,
                'telephone' => $client->telephone,
                'type_client' => $client->type_client,
                'tarif' => (int) ($client->tarif ?? 1),
                'id_frs' => $client->id_frs,
            ],
        ], 'Email vérifié');
    }

    public function resendEmailCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $client = Client::query()->where('email', $data['email'])->first();
        if (! $client) {
            return $this->notFound();
        }

        if ($client->type_client !== 'simple') {
            return $this->error('Non autorisé', null, 403);
        }

        if (! empty($client->email_verified_at)) {
            return $this->success([
                'verification_required' => false,
                'email' => $client->email,
            ], 'Email déjà vérifié');
        }

        if (! $this->sendEmailVerificationCode($client)) {
            return $this->error('Impossible d’envoyer le code', null, 500);
        }

        return $this->success([
            'verification_required' => true,
            'email' => $client->email,
            'expires_in_minutes' => 10,
        ], 'Code renvoyé');
    }

    private function sendEmailVerificationCode(Client $client): bool
    {
        $code = (string) random_int(100000, 999999);

        $client->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => Carbon::now()->addMinutes(10),
            'email_verified_at' => null,
        ])->save();

        $subject = 'Code de vérification email';
        $body = "Votre code de vérification est : {$code}\n\nCe code expire dans 10 minutes.\n\n" . config('app.name');

        try {
            $resendKey = (string) (config('services.resend.key') ?? '');
            $from = (string) (config('services.resend.from') ?? '');

            if ($resendKey !== '' && $from !== '') {
                $res = Http::timeout(10)
                    ->acceptJson()
                    ->withToken($resendKey)
                    ->post('https://api.resend.com/emails', [
                        'from' => $from,
                        'to' => [$client->email],
                        'subject' => $subject,
                        'text' => $body,
                    ]);

                if ($res->successful()) {
                    return true;
                }

                $client->forceFill([
                    'email_verification_code_hash' => null,
                    'email_verification_expires_at' => null,
                ])->save();

                return false;
            }

            Mail::raw($body, function ($message) use ($client, $subject) {
                $message->to($client->email)->subject($subject);
            });

            return true;
        } catch (\Throwable) {
            $client->forceFill([
                'email_verification_code_hash' => null,
                'email_verification_expires_at' => null,
            ])->save();

            return false;
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return $this->success(null, 'Déconnexion réussie');
    }

    public function me(Request $request)
    {
        /** @var Client $client */
        $client = $request->user();

        $frs = $client->fournisseur ?? Fournisseur::single();
        if ($frs && (int) $frs->admin_approval_required === 1 && (int) $client->approved_by_admin !== 1) {
            $token = $client->currentAccessToken();
            if ($token) {
                $token->delete();
            }
            return $this->error("Compte en attente d'approbation par l'administrateur", [
                'approval_pending' => true,
            ], 403);
        }

        $fournisseur = null;
        if ($client->id_frs) {
            $frsData = Fournisseur::query()->find($client->id_frs);
            if ($frsData) {
                $fournisseur = [
                    'id' => $frsData->id,
                    'nom_frs' => $frsData->nom_frs,
                    'email' => $frsData->email,
                    'telephone' => $frsData->telephone,
                ];
            }
        }

        return $this->success([
            'id' => $client->id,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'email' => $client->email,
            'telephone' => $client->telephone,
            'adresse' => $client->adresse,
            'id_wilaya' => $client->id_wilaya,
            'id_commune' => $client->id_commune,
            'type_client' => $client->type_client,
            'tarif' => (int) ($client->tarif ?? 1),
            'id_frs' => $client->id_frs,
            'fournisseur' => $fournisseur,
        ], 'Profil');
    }

    public function updateProfil(AuthUpdateProfileRequest $request)
    {
        /** @var Client $client */
        $client = $request->user();
        $client->update($request->validated());

        return $this->success([
            'id' => $client->id,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'telephone' => $client->telephone,
            'adresse' => $client->adresse,
            'id_wilaya' => $client->id_wilaya,
            'id_commune' => $client->id_commune,
        ], 'Profil mis à jour');
    }

    public function changePassword(AuthChangePasswordRequest $request)
    {
        /** @var Client $client */
        $client = $request->user();
        $data = $request->validated();

        if (! Hash::check($data['current_password'], $client->password)) {
            return $this->error('Ancien mot de passe incorrect', null, 422);
        }

        $client->update([
            'password' => Hash::make($data['password']),
        ]);

        return $this->success(null, 'Mot de passe mis à jour');
    }
}
