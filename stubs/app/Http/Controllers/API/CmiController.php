<?php

namespace App\Http\Controllers\API;

use App\Events\CmiCallbackReceived;
use App\Services\CmiService;
use App\ValueObject\CmiCallbackData;
use App\ValueObject\CmiOrderData;
use Illuminate\Http\Request;
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

    public function handleCallback(Request $request, CmiService $cmiService)
    {
        $isValid = $cmiService->verifyCallbackHash($request->all());

        if (! $isValid) {
            logger()->warning('CMI callback: invalid hash', $request->all());

            throw new UnauthorizedException('Invalid callback hash');
        }

        event(new CmiCallbackReceived(CmiCallbackData::fromRequest($request)));

        return response()->noContent(200);
    }

    public function handleOk(Request $request)
    {
        $data = $request->all();

        logger()->info('Received CMI ok redirect', $data);

        return view('cmi.ok', [
            'orderId' => $data['oid'] ?? null,
            'amount' => $data['amount'] ?? null,
        ]);
    }

    public function handleFail(Request $request)
    {
        $data = $request->all();

        logger()->info('Received CMI fail redirect', $data);

        return view('cmi.fail', [
            'orderId' => $data['oid'] ?? null,
            'errorMessage' => $data['ErrMsg'] ?? ($data['errmsg'] ?? null),
        ]);
    }
}
