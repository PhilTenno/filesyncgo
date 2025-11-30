<?php

declare(strict_types=1);

namespace PhilTenno\FileSyncGo\Rate;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class RateLimiter
{
    private const LIMIT = 24;
    private const WINDOW_SECONDS = 86400; // 24h

    private Connection $conn;
    private LoggerInterface $logger;

    public function __construct(Connection $conn, LoggerInterface $logger)
    {
        $this->conn = $conn;
        $this->logger = $logger;
    }

    /**
     * Attempt to consume one request slot for the given token id.
     *
     * Throws RateLimitExceededException when the limit is reached.
     *
     * This method is atomic: it uses a transaction + row locking (SELECT ... FOR UPDATE).
     *
     * @param int $tokenId tl_filesync_tokens.id
     *
     * @throws RateLimitExceededException
     * @throws \Throwable for DB errors
     */
    public function consume(int $tokenId): void
    {
        $now = time();

        $this->conn->beginTransaction();
        try {
            // Lock the row for this token if it exists
            $row = $this->conn->fetchAssociative(
                'SELECT id, window_start, count FROM tl_filesync_rate_entries WHERE token_id = ? FOR UPDATE',
                [$tokenId]
            );

            if ($row === false || $row === null) {
                // No entry yet -> create one
                $this->conn->insert('tl_filesync_rate_entries', [
                    'tstamp'       => $now,
                    'token_id'     => $tokenId,
                    'window_start' => $now,
                    'count'        => 1,
                ]);
                $this->conn->commit();

                $this->logger->info('RateLimiter: created new window for token.', ['token_id' => $tokenId]);
                return;
            }

            $id = (int) $row['id'];
            $windowStart = (int) $row['window_start'];
            $count = (int) $row['count'];

            if (($now - $windowStart) >= self::WINDOW_SECONDS) {
                // Window expired -> reset
                $this->conn->update('tl_filesync_rate_entries', [
                    'tstamp'       => $now,
                    'window_start' => $now,
                    'count'        => 1,
                ], ['id' => $id]);
                $this->conn->commit();

                $this->logger->info('RateLimiter: window reset for token.', ['token_id' => $tokenId]);
                return;
            }

            if ($count >= self::LIMIT) {
                $this->conn->rollBack();
                $this->logger->warning('RateLimiter: limit exceeded.', ['token_id' => $tokenId]);
                throw new RateLimitExceededException('Rate limit exceeded.');
            }

            // Increment counter
            $this->conn->update('tl_filesync_rate_entries', [
                'tstamp' => $now,
                'count'  => $count + 1,
            ], ['id' => $id]);

            $this->conn->commit();

            $this->logger->info('RateLimiter: incremented count.', ['token_id' => $tokenId, 'new_count' => $count + 1]);
            return;
        } catch (RateLimitExceededException $e) {
            // Known exception: already handled above after rollback
            throw $e;
        } catch (\Throwable $e) {
            // Ensure rollback on error
            if ($this->conn->isTransactionActive()) {
                $this->conn->rollBack();
            }
            $this->logger->error('RateLimiter: DB error.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}