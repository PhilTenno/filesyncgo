<?php

namespace PhilTenno\FileSyncGo\Controller;

use PhilTenno\FileSyncGo\Service\TokenManager;
use PhilTenno\FileSyncGo\Service\SyncService;
use PhilTenno\FileSyncGo\Service\RateLimiter;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TriggerController
{
    private TokenManager $tokenManager;
    private SyncService $syncService;
    private RateLimiter $rateLimiter;
    private LoggerInterface $logger;

    public function __construct(
        TokenManager $tokenManager,
        SyncService $syncService,
        RateLimiter $rateLimiter,
        LoggerInterface $logger
    ) {
        $this->tokenManager = $tokenManager;
        $this->syncService = $syncService;
        $this->rateLimiter = $rateLimiter;
        $this->logger = $logger;
    }

    #[Route('/filesyncgo/trigger', name: 'filesyncgo.trigger', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 1) Body must be empty
            $content = (string) $request->getContent();
            if ($content !== '') {
                $this->logger->warning('Invalid request: non-empty body');
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
            }

            // 2) Parse Authorization header: "Bearer <token>"
            $auth = $request->headers->get('Authorization', '');
            if (!is_string($auth) || stripos($auth, 'Bearer ') !== 0) {
                $this->logger->warning('Invalid token');
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid token.'], 401);
            }

            $token = trim(substr($auth, 7));
            if ($token === '') {
                $this->logger->warning('Invalid token');
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid token.'], 401);
            }

            // 3) Verify token and retrieve entity (time-constant inside TokenManager)
            $tokenEntity = $this->tokenManager->findTokenEntityByPlain($token);
            if (!$tokenEntity) {
                $this->logger->warning('Invalid token');
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid token.'], 401);
            }

            // 4) Rate limiting (real DB-based limiter)
            if (!$this->rateLimiter->allowRequest($tokenEntity)) {
                $this->logger->warning('Rate limit exceeded');
                return new JsonResponse(['status' => 'error', 'message' => 'Rate limit exceeded.'], 429);
            }

            // 5) Trigger Contao global file synchronization
            $this->syncService->sync();

            $this->logger->info('File synchronization triggered successfully');
            return new JsonResponse(['status' => 'success', 'message' => 'File synchronized.'], 200);
        } catch (\Throwable $e) {
            // Log minimal error without sensitive data
            $this->logger->error('Internal server error during filesync trigger: ' . $e->getMessage());
            return new JsonResponse(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }
}