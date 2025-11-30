<?php

declare(strict_types=1);

namespace PhilTenno\FileSyncGo\Controller;

use PhilTenno\FileSyncGo\Rate\RateLimitExceededException;
use PhilTenno\FileSyncGo\Rate\RateLimiter;
use PhilTenno\FileSyncGo\Security\TokenVerifier;
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

            // Verify token (TokenVerifier must perform time-constant checks)
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
            if ($this->container->has('contao.files_synchronizer')) {
                $syncService = $this->container->get('contao.files_synchronizer');

                if (is_object($syncService)) {
                    if (method_exists($syncService, 'synchronize')) {
                        $syncService->synchronize();
                    } elseif (method_exists($syncService, 'sync')) {
                        $syncService->sync();
                    } else {
                        $this->logger->info('Files synchronizer service found but unknown API; falling back to CLI.', ['action' => 'trigger']);
                        $this->runCliSync();
                    }
                } else {
                    $this->runCliSync();
                }
            } else {
                // Fallback to CLI
                $this->runCliSync();
            }

            $this->logger->info('File synchronized.', ['action' => 'trigger', 'token_id' => $tokenId]);
            return new JsonResponse(['status' => 'success', 'message' => 'File synchronized.'], 200);
        } catch (\Throwable $e) {
            // Log minimal error (no sensitive data)
            $this->logger->error('Internal server error.', ['action' => 'trigger', 'error' => $e->getMessage()]);
            return new JsonResponse(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }

    private function runCliSync(): void
    {
        // Typical console locations: vendor/bin/contao-console or bin/console
        // Prefer vendor/bin/contao-console by default
        $consoleCandidates = [
            $this->projectDir . '/vendor/bin/contao-console',
            $this->projectDir . '/bin/console',
        ];

        $console = null;
        foreach ($consoleCandidates as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                $console = $candidate;
                break;
            }
        }

        if ($console === null) {
            throw new \RuntimeException('No console binary found for CLI fallback.');
        }

        // Build safe shell command
        $cmd = escapeshellcmd($console) . ' contao:files:sync';

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(300); // 5 minutes
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Files sync command failed: ' . $process->getErrorOutput());
        }
    }
}