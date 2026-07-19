<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Commune;
use App\Models\Fournisseur;
use App\Models\Wilaya;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Throwable;

class ClientAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.client-login', ['title' => __('Connexion')]);
    }

    public function login(Request $request): RedirectResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $client = Client::query()->where('email', $credentials['email'])->first();

            if (! $client || ! Hash::check($credentials['password'], $client->password)) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('Identifiants invalides.')]);
            }

            if ((int) $client->actif !== 1) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('Compte désactivé.')]);
            }

            if ($client->type_client === 'simple' && empty($client->email_verified_at)) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('Email non vérifié. Veuillez vérifier votre boîte mail.')]);
            }

            $request->session()->regenerate();
            $request->session()->put([
                'role' => 'client',
                'client_id' => $client->id,
            ]);

            return redirect()->intended('/');
        } catch (QueryException $e) {
            report($e);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('Erreur base de données. Veuillez contacter l’administrateur.')]);
        } catch (Throwable $e) {
            report($e);
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('Erreur serveur. Veuillez réessayer.')]);
        }
    }

    public function showRegister(): View
    {
        $wilayas = Wilaya::query()->orderBy('ID_WILAYA')->get(['ID_WILAYA', 'WILAYA']);
        $defaultWilaya = (int) ($wilayas->first()?->ID_WILAYA ?? 1);
        $communes = Commune::query()
            ->where('ID_WILAYA', $defaultWilaya)
            ->orderBy('COMMUNE')
            ->get(['ID_COMMUNE', 'COMMUNE', 'ID_WILAYA']);

        return view('auth.client-register', [
            'title' => __('Créer un compte'),
            'wilayas' => $wilayas,
            'communes' => $communes,
            'default_wilaya' => $defaultWilaya,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'telephone' => ['required', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'id_wilaya' => ['nullable', 'integer', 'exists:wilaya,ID_WILAYA'],
            'id_commune' => ['nullable', 'integer', 'exists:commune,ID_COMMUNE'],
        ]);

        $idWilaya = isset($data['id_wilaya'])
            ? (int) $data['id_wilaya']
            : (int) (DB::table('wilaya')->min('ID_WILAYA') ?? 1);

        $idCommune = isset($data['id_commune'])
            ? (int) $data['id_commune']
            : (int) (DB::table('commune')->where('ID_WILAYA', $idWilaya)->min('ID_COMMUNE') ?? 1);

        $existing = Client::query()->where('email', $data['email'])->first();

        if ($existing && ! empty($existing->email_verified_at)) {
            return back()
                ->withInput($request->only('nom', 'prenom', 'email', 'telephone', 'adresse', 'id_wilaya', 'id_commune'))
                ->withErrors(['email' => __('Email déjà utilisé.')]);
        }

        if ($existing && $existing->type_client !== 'simple') {
            return back()
                ->withInput($request->only('nom', 'prenom', 'email', 'telephone', 'adresse', 'id_wilaya', 'id_commune'))
                ->withErrors(['email' => __('Email déjà utilisé.')]);
        }

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
            'id_frs' => (int) (Fournisseur::single()?->id ?? 0) ?: null,
            'actif' => 1,
            'email_verified_at' => null,
        ];

        $client = $existing
            ? tap($existing)->update($payload)
            : Client::create($payload);

        if (! $this->sendEmailVerificationCode($client)) {
            return back()
                ->withInput($request->only('nom', 'prenom', 'email', 'telephone', 'adresse', 'id_wilaya', 'id_commune'))
                ->withErrors(['email' => __('Impossible d’envoyer le code. Vérifiez la configuration Resend/Mail.')]);
        }

        $request->session()->forget(['role', 'client_id']);
        $request->session()->put([
            'pending_client_id' => $client->id,
            'pending_client_email' => $client->email,
        ]);

        return redirect()->to('/register')->with('success', __('Un code de vérification a été envoyé par email.'));
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $pendingId = (int) $request->session()->get('pending_client_id');
        if ($pendingId <= 0) {
            return redirect()->to('/register')->withErrors(['code' => __('Session expirée. Veuillez refaire l’inscription.')]);
        }

        $client = Client::query()->find($pendingId);
        if (! $client) {
            return redirect()->to('/register')->withErrors(['code' => __('Compte introuvable.')]);
        }

        if ($client->type_client !== 'simple') {
            return redirect()->to('/register')->withErrors(['code' => __('Compte invalide.')]);
        }

        if (! empty($client->email_verified_at)) {
            $request->session()->forget(['pending_client_id', 'pending_client_email']);
            return redirect()->to('/login')->with('success', __('Email déjà vérifié. Vous pouvez vous connecter.'));
        }

        if (empty($client->email_verification_code_hash) || empty($client->email_verification_expires_at)) {
            return redirect()->to('/register')->withErrors(['code' => __('Code invalide.')]);
        }

        $expiresAt = Carbon::parse($client->email_verification_expires_at);
        if (Carbon::now()->greaterThan($expiresAt)) {
            return redirect()->to('/register')->withErrors(['code' => __('Code expiré. Cliquez sur "Renvoyer le code".')]);
        }

        if (! Hash::check($data['code'], $client->email_verification_code_hash)) {
            return redirect()->to('/register')->withErrors(['code' => __('Code incorrect.')]);
        }

        $client->forceFill([
            'email_verified_at' => Carbon::now(),
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
        ])->save();

        $request->session()->forget(['pending_client_id', 'pending_client_email']);
        $request->session()->regenerate();
        $request->session()->put([
            'role' => 'client',
            'client_id' => $client->id,
        ]);

        return redirect()->to('/')->with('success', __('Email vérifié. Bienvenue.'));
    }

    public function resendEmailCode(Request $request): RedirectResponse
    {
        $pendingId = (int) $request->session()->get('pending_client_id');
        if ($pendingId <= 0) {
            return redirect()->to('/register')->withErrors(['code' => __('Session expirée. Veuillez refaire l’inscription.')]);
        }

        $client = Client::query()->find($pendingId);
        if (! $client) {
            return redirect()->to('/register')->withErrors(['code' => __('Compte introuvable.')]);
        }

        if ($client->type_client !== 'simple') {
            return redirect()->to('/register')->withErrors(['code' => __('Compte invalide.')]);
        }

        if (! empty($client->email_verified_at)) {
            $request->session()->forget(['pending_client_id', 'pending_client_email']);
            return redirect()->to('/login')->with('success', __('Email déjà vérifié. Vous pouvez vous connecter.'));
        }

        if (! $this->sendEmailVerificationCode($client)) {
            return redirect()->to('/register')->withErrors(['code' => __('Impossible d’envoyer le code. Vérifiez la configuration Resend/Mail.')]);
        }

        return redirect()->to('/register')->with('success', __('Code renvoyé.'));
    }

    private function sendEmailVerificationCode(Client $client): bool
    {
        $code = (string) random_int(100000, 999999);

        $client->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => Carbon::now()->addMinutes(10),
            'email_verified_at' => null,
        ])->save();

        $subject = __('Code de vérification email');
        $body = __('Votre code de vérification est : :code\n\nCe code expire dans 10 minutes.\n\n:app', [
            'code' => $code,
            'app' => config('app.name'),
        ]);

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

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['role', 'client_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to('/');
    }
}
