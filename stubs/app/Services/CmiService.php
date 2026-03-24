<?php

namespace App\Services;

use App\ValueObject\CmiOrderData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class CmiService
{
    /**
     * Billing fields that must have accents stripped before hash calculation.
     * Defined by the CMI integration spec and the PHP reference scripts.
     */
    private const BILLING_FIELDS = [
        'BillToName',
        'BillToCompany',
        'BillToStreet1',
        'BillToCity',
        'BillToStateProv',
        'BillToPostalCode',
        'BillToCountry',
    ];

    public function __construct(private readonly Repository $repository) {}

    public function redirectToGateway(CmiOrderData $orderData): Response
    {
        $params = $this->buildPaymentParams($orderData);

        return $this->renderAutoSubmitForm($params);
    }

    /**
     * Build all payment parameters and append the computed HASH.
     */
    private function buildPaymentParams(CmiOrderData $orderData): Collection
    {
        $params = new Collection([
            'clientid' => $this->repository->get('cmi.client_id'),
            'amount' => number_format($orderData->amount, 2, '.', ''),
            'oid' => $orderData->orderId,
            'okUrl' => $this->repository->get('cmi.ok_url'),
            'failUrl' => $this->repository->get('cmi.fail_url'),
            'callbackUrl' => $this->repository->get('cmi.callback_url'),
            'CallbackResponse' => 'true',        // required for host-to-host callback
            'shopurl' => $this->repository->get('cmi.shop_url'),
            'TranType' => 'PreAuth',
            'currency' => $this->repository->get('cmi.currency'),
            'rnd' => microtime(),   // random nonce used in hash
            'lang' => $this->repository->get('cmi.lang'),
            'storetype' => '3D_PAY_HOSTING',
            'hashAlgorithm' => 'ver3',
            'refreshtime' => '5',
            'encoding' => 'UTF-8',       // excluded from hash, but sent in form

            // Billing fields — accents stripped per CMI requirement
            'BillToName' => $this->stripAccents(trim($orderData->name)),
            'BillToCompany' => $this->stripAccents(trim($orderData->company)),
            'BillToStreet1' => $this->stripAccents(trim($orderData->address)),
            'BillToCity' => $this->stripAccents(trim($orderData->city)),
            'BillToStateProv' => $this->stripAccents(trim($orderData->state)),
            'BillToPostalCode' => trim($orderData->postalCode),
            'BillToCountry' => trim($orderData->countryCode),
            'email' => trim($orderData->email),
            'tel' => trim($orderData->phone),
        ]);

        // HASH must be added last — it is computed from all other params
        $params->put('HASH', $this->computeRequestHash($params));

        return $params;
    }

    /**
     * Build payment params from order data and return a self-submitting HTML page
     * that POSTs all params to the CMI gateway.
     * This is the only way to do a POST "redirect" to CMI from a server response.
     */
    private function renderAutoSubmitForm(Collection $params): Response
    {
        $gatewayUrlFromConfig = $this->repository->get('cmi.gateway_url');

        $safeGatewayUrl = htmlspecialchars((string) $gatewayUrlFromConfig, ENT_QUOTES, 'UTF-8');

        $fields = implode(PHP_EOL, array_map(
            static fn ($name, $value): string => sprintf(
                '        <input type="hidden" name="%s" value="%s">',
                htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
            ),
            $params->keys()->all(),
            $params->values()->all(),
        ));

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="robots" content="noindex,nofollow">
            <title>Redirection vers le paiement sécurisé...</title>
            <style>body{font-family:sans-serif;text-align:center;padding-top:80px;}</style>
        </head>
        <body onload="document.getElementById('cmi_form').submit()">
            <p>Redirection vers la page de paiement sécurisé CMI, veuillez patienter&hellip;</p>
            <form id="cmi_form" method="POST" action="{$safeGatewayUrl}">
        {$fields}
            </form>
        </body>
        </html>
        HTML;

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Verify the HASH received from CMI on the host-to-host callback.
     *
     * CMI may HTML-encode values in the callback POST — the reference
     * callback.php uses html_entity_decode() + trailing newline strip before hashing.
     */
    public function verifyCallbackHash(array $postData): bool
    {
        $received = $postData['HASH'] ?? '';
        $computed = $this->computeCallbackHash($postData);

        // hash_equals prevents timing attacks
        return hash_equals($computed, $received);
    }

    /**
     * Verify the HASH received from CMI on the okUrl / failUrl browser redirect.
     *
     * The ok.php / fail.php reference scripts use trim + html_entity_decode.
     */
    public function verifyRedirectHash(array $postData): bool
    {
        $received = $postData['HASH'] ?? '';
        $computed = $this->computeRedirectHash($postData);

        return hash_equals($computed, $received);
    }

    // ─────────────────────────────────────────────────────────────────────
    // HASH COMPUTATION  (three contexts, same algorithm, different normalisation)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Hash for the outgoing payment request (mirrors SendData.php).
     *
     * Normalisation per field:
     *   Billing fields → trim + stripAccents
     *   All other fields → trim
     */
    private function computeRequestHash(Collection $params): string
    {
        $keys = $params->keys()->all();

        natcasesort($keys);

        $hashval = '';
        foreach ($keys as $key) {
            if ($this->isExcludedFromHash($key)) {
                continue;
            }

            $value = in_array($key, self::BILLING_FIELDS, true)
                ? trim($this->stripAccents((string) $params->get($key)))
                : trim((string) $params->get($key));

            $hashval .= $this->escapeForHash($this->applyDocumentRule($value)).'|';
        }

        $hashval .= $this->escapeForHash($this->repository->get('cmi.store_key'));

        return base64_encode(pack('H*', hash('sha512', $hashval)));
    }

    /**
     * Hash for incoming host-to-host callback (mirrors callback.php).
     *
     * Normalisation: html_entity_decode + strip trailing newline.
     * Note: CMI may send multiple callbacks per order (failed attempts then success).
     */
    private function computeCallbackHash(array $postData): string
    {
        $keys = array_keys($postData);
        natcasesort($keys);

        $hashval = '';
        foreach ($keys as $key) {
            if ($this->isExcludedFromHash($key)) {
                continue;
            }

            // Replicates: html_entity_decode(preg_replace("/\n$/", "", $v), ENT_QUOTES, 'UTF-8')
            $value = html_entity_decode(
                (string) preg_replace('/\n$/', '', (string) $postData[$key]),
                ENT_QUOTES,
                'UTF-8'
            );

            $hashval .= $this->escapeForHash($this->applyDocumentRule($value)).'|';
        }

        $hashval .= $this->escapeForHash($this->repository->get('cmi.store_key'));

        return base64_encode(pack('H*', hash('sha512', $hashval)));
    }

    /**
     * Hash for incoming okUrl / failUrl browser redirect (mirrors ok.php / fail.php).
     *
     * Normalisation: trim + html_entity_decode.
     */
    private function computeRedirectHash(array $postData): string
    {
        $keys = array_keys($postData);
        natcasesort($keys);

        $hashval = '';
        foreach ($keys as $key) {
            if ($this->isExcludedFromHash($key)) {
                continue;
            }

            $value = trim(html_entity_decode((string) $postData[$key], ENT_QUOTES, 'UTF-8'));

            $hashval .= $this->escapeForHash($this->applyDocumentRule($value)).'|';
        }

        $hashval .= $this->escapeForHash($this->repository->get('cmi.store_key'));

        return base64_encode(pack('H*', hash('sha512', $hashval)));
    }

    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * CMI security rule (doc v2.0 p.14):
     * Replace the single character immediately after the substring "document"
     * (case-insensitive) with a dot during hash calculation.
     *
     * This neutralises script injection payloads like document.cookie,
     * document.write, etc., even when formatted without the dot.
     *
     * Examples:
     *   "document abc"  → "document.abc"   (space → dot)
     *   "documentabc"   → "document.bc"    (a → dot)
     *   "document.cookie" → "document.cookie" (dot → dot, no change)
     */
    private function applyDocumentRule(string $value): string
    {
        return preg_replace('/document(.)/i', 'document.', $value);
    }

    /**
     * Escape backslashes, then pipes — required before concatenating into hashval.
     * Order matters: backslash must be escaped first.
     */
    private function escapeForHash(string $value): string
    {
        return str_replace('|', '\\|', str_replace('\\', '\\\\', $value));
    }

    /**
     * The "encoding" and "hash"/"HASH" keys are excluded from hash calculation.
     */
    private function isExcludedFromHash(string $key): bool
    {
        $lower = strtolower($key);

        return $lower === 'hash' || $lower === 'encoding';
    }

    /**
     * Strip accents from billing address fields.
     * Direct port of str_without_accents() from the CMI PHP reference scripts.
     */
    public function stripAccents(string $str, string $charset = 'utf-8'): string
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', (string) $str);
        $str = preg_replace('#&[^;]+;#', '', (string) $str);
        $str = preg_replace('/[^a-zA-Z0-9_ -]/s', '', (string) $str);

        return $str;
    }
}
