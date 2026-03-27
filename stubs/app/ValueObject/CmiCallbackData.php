<?php

namespace App\ValueObject;

use App\Enums\CardBrandEnum;
use Illuminate\Http\Request;

final class CmiCallbackData
{
    public function __construct(
        public readonly string $oid,
        public readonly ?string $response,
        public readonly ?string $procReturnCode,
        public readonly ?int $mdStatus,
        public readonly ?float $amount,
        public readonly ?string $transId,
        public readonly ?string $authCode,
        public readonly ?string $maskedPan,
        public readonly ?string $errorMessage,
        public readonly ?string $paymentType,
        public readonly ?CardBrandEnum $cardBrand,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            oid: (string) $request->input('oid', ''),
            response: $request->input('Response'),
            procReturnCode: $request->input('ProcReturnCode'),
            mdStatus: $request->filled('mdStatus') ? (int) $request->input('mdStatus') : null,
            amount: $request->filled('amount') ? (float) $request->input('amount') : null,
            transId: $request->input('TransId'),
            authCode: $request->input('AuthCode'),
            maskedPan: $request->input('MaskedPan'),
            cardBrand: CardBrandEnum::tryFrom($request->string('EXTRA_CARDBRAND', '')->toString()),
            errorMessage: $request->input('mdErrorMsg') ?: $request->input('ErrMsg'),
            paymentType: $request->input('paymentType'),
        );
    }
}
