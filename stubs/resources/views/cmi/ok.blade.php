@extends('cmi.layout')

@section('title', 'Paiement confirmé — ' . config('app.name', 'Laravel'))

@section('content')
    {{-- Status banner --}}
    <div class="flex items-center justify-center gap-3 px-6 py-5 bg-secondary/20 border-b border-border">
        <span class="flex items-center justify-center w-12 h-12 rounded-full bg-secondary/30">
            <svg viewBox="0 0 24 24" fill="none" class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="#1c3aa4" stroke-width="2" />
                <path d="M7.5 12.5l3 3 6-6" stroke="#1c3aa4" stroke-width="2.2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </span>
        <div>
            <p class="text-sm font-medium text-muted-foreground">Statut du paiement</p>
            <p class="text-lg font-bold text-primary">Confirmé</p>
        </div>
    </div>

    {{-- Body --}}
    <div class="px-8 py-8 flex flex-col items-center text-center gap-4">
        <h1 class="text-2xl font-bold text-foreground">Paiement réussi !</h1>
        <p class="text-muted-foreground leading-relaxed">
            Votre paiement a été traité avec succès. Votre réservation est maintenant confirmée.
        </p>

        @if($orderId)
            <div class="mt-2 w-full rounded-xl bg-muted px-5 py-4 text-left">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Référence de commande</p>
                <p class="font-semibold text-foreground font-mono text-sm break-all">{{ $orderId }}</p>
            </div>
        @endif

        @if($amount)
            <div class="w-full rounded-xl bg-muted px-5 py-4 text-left">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Montant payé</p>
                <p class="font-semibold text-foreground text-lg">{{ $amount }} MAD</p>
            </div>
        @endif
    </div>

    {{-- Footer note --}}
    <div class="px-8 pb-8">
        <p class="text-center text-sm text-muted-foreground">
            Vous pouvez fermer cette fenêtre ou retourner sur l'application.
        </p>
    </div>
@endsection