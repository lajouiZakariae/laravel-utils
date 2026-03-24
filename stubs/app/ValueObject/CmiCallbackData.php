<?php

namespace App\ValueObject;

use App\CardBrandEnum;
use Illuminate\Http\Request;

final readonly class CmiCallbackData
{
    public function __construct(
        public string $oid,
        public ?string $response,
        public ?string $procReturnCode,
        public int $mdStatus,
        public ?float $amount,
        public ?string $transId,
        public ?string $authCode,
        public ?string $maskedPan,
        public ?string $errorMessage,
        public ?string $paymentType,
        public ?CardBrandEnum $cardBrand,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            oid: (string) $request->input('oid', ''),
            response: $request->input('Response'),
            procReturnCode: $request->input('ProcReturnCode'),
            mdStatus: (int) $request->input('mdStatus', 0),
            amount: $request->filled('amount') ? (float) $request->input('amount') : null,
            transId: $request->input('TransId'),
            authCode: $request->input('AuthCode'),
            maskedPan: $request->input('MaskedPan'),
            // EXTRA.CARDBRAND uses a literal dot — not accessible via dot-notation input()
            cardBrand: CardBrandEnum::tryFrom((string) ($request->all()['EXTRA.CARDBRAND'] ?? '')),
            errorMessage: $request->input('mdErrorMsg') ?: $request->input('ErrMsg'),
            paymentType: $request->input('paymentType'),
        );
    }
}
