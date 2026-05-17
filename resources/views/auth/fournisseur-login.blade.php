@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
    <div class="w-full max-w-md">
        <div class="text-center mb-6 text-slate-900 dark:text-white">
            <div class="text-3xl font-extrabold tracking-wide">Soltane Dz</div>
            <div class="text-sm opacity-80 mt-1">Espace Fournisseur</div>
        </div>

        <div class="rounded-2xl p-8 shadow-2xl bg-white border border-slate-200 dark:bg-slate-900 dark:border-slate-800">
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ url('/fournisseur/login') }}" class="space-y-4" x-data="{ show: false }">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">Email</label>
                    <input name="email"
                           type="email"
                           value="{{ old('email') }}"
                           required
                           autocomplete="email"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-[#1E6FD9] focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:ring-blue-900/50" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">Password</label>
                    <div class="relative">
                        <input name="password"
                               :type="show ? 'text' : 'password'"
                               required
                               autocomplete="current-password"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-12 outline-none focus:border-[#1E6FD9] focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:ring-blue-900/50" />
                        <button type="button"
                                class="absolute inset-y-0 right-0 px-4 text-slate-500 hover:text-slate-700 dark:text-slate-300 dark:hover:text-slate-100"
                                @click="show = !show">
                            <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full rounded-xl py-3 font-bold text-white shadow-lg"
                        style="background: linear-gradient(135deg, #1E6FD9 0%, #0A3D7A 100%);">
                    Se connecter
                </button>
            </form>

        </div>
    </div>
</div>
@endsection
