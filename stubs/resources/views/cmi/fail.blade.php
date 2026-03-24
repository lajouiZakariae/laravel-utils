@extends('cmi.layout')

@section('title', 'Paiement échoué — ' . config('app.name', 'WashRoc'))

@section('content')
    {{-- Status banner --}}
    <div class="flex items-center justify-center gap-3 px-6 py-5 bg-destructive/10 border-b border-border">
        <span class="flex items-center justify-center w-12 h-12 rounded-full bg-destructive/15">
            <svg viewBox="0 0 24 24" fill="none" class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="2" />
                <path d="M15 9l-6 6M9 9l6 6" stroke="#ef4444" stroke-width="2.2" stroke-linecap="round" />
            </svg>
        </span>
        <div>
            <p class="text-sm font-medium text-muted-foreground">Statut du paiement</p>
            <p class="text-lg font-bold text-destructive">Échoué</p>
        </div>
    </div>

    {{-- Body --}}
    <div class="px-8 py-8 flex flex-col items-center text-center gap-4">
        <h1 class="text-2xl font-bold text-foreground">Paiement non abouti</h1>
        <p class="text-muted-foreground leading-relaxed">
            Votre paiement n'a pas pu être traité. Veuillez réessayer ou contacter votre banque si le problème persiste.
        </p>

        @if($orderId)
            <div class="mt-2 w-full rounded-xl bg-muted px-5 py-4 text-left">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Référence de commande</p>
                <p class="font-semibold text-foreground font-mono text-sm break-all">{{ $orderId }}</p>
            </div>
        @endif

        @if($errorMessage)
            <div class="w-full rounded-xl bg-destructive/10 border border-destructive/20 px-5 py-4 text-left">
                <p class="text-xs font-medium text-destructive uppercase tracking-wide mb-1">Raison</p>
                <p class="text-sm text-foreground">{{ $errorMessage }}</p>
            </div>
        @endif
    </div>

    {{-- Footer note --}}
    <div class="px-8 pb-8">
        <p class="text-center text-sm text-muted-foreground">
            Vous pouvez fermer cette fenêtre ou retourner sur l'application pour réessayer.
        </p>
    </div>
@endsection