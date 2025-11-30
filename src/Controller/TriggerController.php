<?php

declare(strict_types=1);

namespace PhilTenno\FilesyncGo\Controller;

use PhilTenno\FilesyncGo\Rate\RateLimitExceededException;
use PhilTenno\FilesyncGo\Rate\RateLimiter;
use PhilTenno\FilesyncGo\Security\TokenVerifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class TriggerController
{
    private TokenVerifier $tokenVerifier;
    private RateLimiter $rateLimiter;
    private LoggerInterface $logger;
    private ContainerInterface $container;
    private string $projectDir;

    public function __construct(
        TokenVerifier $tokenVerifier,
        RateLimiter $rateLimiter,
        LoggerInterface $logger,
        ContainerInterface $container,
        string $projectDir
    ) {
        $this->tokenVerifier = $tokenVerifier;
        $this->rateLimiter = $rateLimiter;
        $this->logger = $logger;
        $this->container = $container;
        $this->projectDir = $projectDir;
    }

    public function trigger(Request $request): JsonResponse
    {
        try {
            // HTTPS required
            if (!$request->isSecure()) {
                $this->logger->warning('Invalid request (non-HTTPS).', ['action' => 'trigger']);
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
            }

            // Body must be empty
            $body = trim((string) $request->getContent());
            if ($body !== '') {
                $this->logger->warning('Invalid request (non-empty body).', ['action' => 'trigger']);
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid request.'], 400);
            }

            // Verify token (TokenVerifier takes care of time-constant checks)
            $tokenEntity = $this->tokenVerifier->verifyRequest($request);
            if ($tokenEntity === null) {
                $this->logger->info('Invalid token.', ['action' => 'trigger']);
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid token.'], 401);
            }

            $tokenId = (int) $tokenEntity->getId();

            // Rate limiter (throws RateLimitExceededException on exceed)
            try {
                $this->rateLimiter->consume($tokenId);
            } catch (RateLimitExceededException $e) {
                $this->logger->warning('Rate limit exceeded.', ['action' => 'trigger', 'token_id' => $tokenId]);
                return new JsonResponse(['status' => 'error', 'message' => 'Rate limit exceeded.'], 429);
            }

            // Trigger the global file sync:
            // 1) Prefer internal service if available
            if ($this->container->has('contao.files_synchronizer')) {
                $syncService = $this->container->get('contao.files_synchronizer');

                // Best-effort: call common method names if present
                if (is_object($syncService)) {
                    if (method_exists($syncService, 'synchronize')) {
                        $syncService->synchronize();
                    } elseif (method_exists($syncService, 'sync')) {
                        $syncService->sync();
                    } else {
                        // Unknown API on service — fallback to CLI
                        $this->logger->info('Files synchronizer service found but no known method; falling back to CLI.', ['action' => 'trigger']);
                        $this->runCliSync();
                    }
                } else {
                    $this->runCliSync();
                }
            } else {
                // 2) Fallback: run console command vendor/bin/contao-console contao:files:sync
                $this->runCliSync();
            }

            $this->logger->info('File synchronized.', ['action' => 'trigger', 'token_id' => $tokenId]);
            return new JsonResponse(['status' => 'success', 'message' => 'File synchronized.'], 200);
        } catch (\Throwable $e) {
            // Generic error handling: do not leak sensitive details
            $this->logger->error('Internal server error.', ['action' => 'trigger', 'error' => $e->getMessage()]);
            return new JsonResponse(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }

    private function runCliSync(): void
    {
        // Build path to contao-console; allow typical location vendor/bin/contao-console
        $console = $this->projectDir . '/vendor/bin/contao-console';

        // Use the Symfony Process component to run the command
        $process = Process::fromShellCommandline(escapeshellcmd($console) . ' contao:files:sync');
        $process->setTimeout(300); // 5 minutes
        $process->run();

        if (!$process->isSuccessful()) {
            // Throw to be caught by outer handler and logged as 500
            throw new \RuntimeException('Files sync command failed: ' . $process->getErrorOutput());
        }

        // Success -> do not log command output (avoid sensitive data)
    }
}