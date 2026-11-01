<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Recruit;

use MagicCMS\Core\Mailer;

final class Recruit
{
    public const STATUSES = ['new' => 'New', 'review' => 'In review', 'interview' => 'Interview', 'accepted' => 'Accepted', 'declined' => 'Declined', 'withdrawn' => 'Withdrawn'];
    public const ACTIVE = ['new', 'review', 'interview'];
    public const TYPES = ['text' => 'Short text', 'textarea' => 'Long text', 'select' => 'Choice list', 'yesno' => 'Yes / no'];

    public static function label(string $status): string
    {
        return t(self::STATUSES[$status] ?? $status);
    }

    public static function enabled(): bool
    {
        return (bool) setting('recruit.enabled', true);
    }

    public static function options(array $q): array
    {
        return array_values(array_filter(array_map('trim', explode('|', (string) $q['options'])), 'strlen'));
    }

    public static function questions(): array
    {
        return db()->all('SELECT * FROM {recruit_questions} WHERE enabled = 1 ORDER BY sort_order, id');
    }

    public static function link(string $token): string
    {
        return abs_url('recruit/status/' . $token);
    }

    /** Active application of this user / e-mail address, if any. */
    public static function activeFor(?int $userId, string $email): ?array
    {
        $in = "'" . implode("','", self::ACTIVE) . "'";
        return db()->one("SELECT * FROM {recruit_applications} WHERE status IN ($in) AND ((? IS NOT NULL AND user_id = ?) OR email = ?) ORDER BY id DESC LIMIT 1", [$userId, $userId, mb_strtolower($email)]);
    }

    public static function mail(string $to, string $subject, string $title, string $body, ?array $button = null): void
    {
        if (valid_email($to)) {
            Mailer::send($to, $subject, Mailer::template($title, '<p>' . nl2br(e($body)) . '</p>', $button));
        }
    }

    /** Remove decided applications after the configured retention period (privacy). */
    public static function housekeeping(): void
    {
        $days = (int) setting('recruit.retention_days', 180);
        if ($days > 0 && random_int(1, 20) === 1) {
            $cut = gmdate('Y-m-d H:i:s', time() - $days * 86400);
            $ids = array_column(db()->all("SELECT id FROM {recruit_applications} WHERE status IN ('accepted','declined','withdrawn') AND updated_at < ? LIMIT 200", [$cut]), 'id');
            foreach ($ids as $id) {
                db()->exec('DELETE FROM {recruit_notes} WHERE application_id = ?', [$id]);
                db()->exec('DELETE FROM {recruit_applications} WHERE id = ?', [$id]);
            }
        }
    }
}
