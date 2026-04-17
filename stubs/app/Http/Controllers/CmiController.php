<?php

namespace App\Http\Controllers;

use App\Events\CmiCallbackReceived;
use App\Services\CmiService;
use App\ValueObject\CmiCallbackData;
use App\ValueObject\CmiOrderData;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Validation\UnauthorizedException;

class CmiController
{
    public function pay(CmiService $cmiService)
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'city' => 'Test City',
            'amount' => 100,
            'order_id' => 'TEST123',
        ];

        return $cmiService->redirectToGateway(CmiOrderData::fromArray($data));
    }

    public function handleCallback(Request $request, CmiService $cmiService, LogManager $logger)
    {
        $isValid = $cmiService->verifyCallbackHash($request->all());

        if (! $isValid) {
            $logger->warning('CMI callback: invalid hash', $request->all());

            throw new UnauthorizedException('Invalid callback hash');
        }

        CmiCallbackReceived::dispatch(CmiCallbackData::fromRequest($request));

        return response()->noContent(200);
    }

    public function handleOk(Request $request, LogManager $logger)
    {
        $data = $request->all();

        $logger->info('Received CMI ok redirect', $data);

        return view('cmi.ok', [
            'orderId' => $data['oid'] ?? null,
            'amount' => $data['amount'] ?? null,
        ]);
    }

    public function handleFail(Request $request, LogManager $logger)
    {
        $logger->info('Received CMI fail redirect', $request->all());

        $orderId = $request->input('oid');
        $amount = $request->input('amount');
        $currency = $request->input('currencyAlphaCode', 'MAD');
        $response = $request->input('Response');        // "Declined" or "Error"
        $procCode = $request->input('ProcReturnCode');  // "99", "51", etc.
        $errCode = $request->input('ErrCode');         // "CORE-5110", etc.
        $errMsg = $request->input('ErrMsg');
        $maskedPan = $request->input('MaskedPan');
        $mdStatus = $request->input('mdStatus');

        // Build a user-friendly error message
        $errorMessage = match (true) {
            ! empty($errMsg) => $errMsg,
            ! empty($errCode) => "Code d'erreur : {$errCode}",
            ! empty($response) => $response,
            default => 'Une erreur est survenue lors du paiement.',
        };

        return view('cmi.fail', compact(
            'orderId',
            'amount',
            'currency',
            'errorMessage',
            'procCode',
            'maskedPan',
        ));
    }
}
