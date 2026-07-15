<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWeb2MWebhookTransaction;
use App\Services\Web2MWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class Web2MWebhookController extends Controller
{
    private const ACCESS_TOKEN = '6AFBE818-C736-D46B-36A4-6A435A9C1887';
    private const ACCESS_TOKENS = [
        self::ACCESS_TOKEN,
        'bd89c64c4987d9814b9a37dcd885b91a5501d00a3d18be7ccb325354cace28c1',
    ];

    public function __invoke(Request $request, Web2MWebhookProcessor $processor): JsonResponse
    {
        $hasValidToken = $this->hasValidBearerToken($request);
        $this->writePaymentLog($request, $hasValidToken ? 'authorized' : 'unauthorized');

        if (!$hasValidToken) {
            return response()->json([
                'status' => false,
                'msg' => 'Access Token khong hop le.',
            ], 401);
        }

        try {
            $payload = $request->all();

            foreach ($processor->transactionItems($payload) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                ProcessWeb2MWebhookTransaction::dispatch($item, $payload)
                    ->delay(now()->addSeconds(random_int(3, 5)));
            }
        } catch (Throwable $exception) {
            $this->writePaymentLog($request, 'exception', $exception);

            return response()->json([
                'status' => false,
                'msg' => 'Cannot process webhook right now.',
            ], 200);
        }

        return response()->json([
            'status' => true,
            'msg' => 'Ok',
        ]);
    }

    private function hasValidBearerToken(Request $request): bool
    {
        $receivedToken = $this->receivedToken($request);

        foreach (self::ACCESS_TOKENS as $configuredToken) {
            if (
                $configuredToken !== ''
                && $configuredToken !== 'change-this-access-token'
                && hash_equals($configuredToken, $receivedToken)
            ) {
                return true;
            }
        }

        return false;
    }

    private function receivedToken(Request $request): string
    {
        $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

        if (str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        $headerName = (string) config('web2m.token_header', 'X-Web2M-Token');
        $token = (string) $request->header($headerName, '');

        if ($token !== '') {
            return $token;
        }

        return (string) $request->query('token', '');
    }

    private function writePaymentLog(Request $request, string $status, ?Throwable $exception = null): void
    {
        $logDir = storage_path('logs');

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $entry = [
            'time' => now()->toDateTimeString(),
            'status' => $status,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'payload' => $request->all(),
        ];

        if ($status !== 'authorized' || $exception) {
            $entry['received_token'] = $this->receivedToken($request);
            $entry['headers'] = $request->headers->all();
            $entry['server_authorization'] = [
                'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
                'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
                'Authorization' => $_SERVER['Authorization'] ?? null,
            ];
            $entry['raw_body'] = $request->getContent();
        }

        if ($exception) {
            $entry['exception'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        file_put_contents(
            $logDir . DIRECTORY_SEPARATOR . 'log-payment.log',
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL . str_repeat('-', 120) . PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
    }
}
