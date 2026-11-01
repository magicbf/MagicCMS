<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Forum;

/** One optional poll per topic. Votes are anonymous (only totals are ever shown). */
final class Polls
{
    public const DURATIONS = [0 => 'No end date', 1 => '1 day', 3 => '3 days', 7 => '7 days', 14 => '14 days', 30 => '30 days', 90 => '90 days'];
    public const RESULTS = ['after_vote' => 'After voting', 'always' => 'Always visible', 'after_close' => 'Only when the poll has ended'];

    public static function enabled(): bool
    {
        return (bool) setting('forum.polls_enabled', true);
    }

    public static function isClosed(array $poll, ?array $topic = null): bool
    {
        return (bool) $poll['is_closed'] || ($poll['closes_at'] && strtotime($poll['closes_at'] . ' UTC') <= time()) || ($topic && $topic['is_locked']);
    }

    /** Poll with options, counts and the viewer's votes, or null. */
    public static function forTopic(int $topicId): ?array
    {
        $p = db()->one('SELECT * FROM {forum_polls} WHERE topic_id = ?', [$topicId]);
        if (!$p) {
            return null;
        }
        $opts = db()->all('SELECT o.id, o.label, (SELECT COUNT(*) FROM {forum_poll_votes} v WHERE v.option_id = o.id) AS votes FROM {forum_poll_options} o WHERE o.poll_id = ? ORDER BY o.sort_order, o.id', [$p['id']]);
        $p['options'] = $opts;
        $p['voters'] = (int) db()->val('SELECT COUNT(DISTINCT user_id) FROM {forum_poll_votes} WHERE poll_id = ?', [$p['id']]);
        $p['mine'] = user() ? array_map('intval', array_column(db()->all('SELECT option_id FROM {forum_poll_votes} WHERE poll_id = ? AND user_id = ?', [$p['id'], user()['id']]), 'option_id')) : [];
        return $p;
    }

    public static function canSeeResults(array $poll, array $topic): bool
    {
        return self::isClosed($poll, null) || $poll['results'] === 'always' || ($poll['results'] === 'after_vote' && $poll['mine']) || (Forum::isMod() && \MagicCMS\Core\Auth::level() >= (int) $topic['author_level']);
    }

    /** Parse the poll part of a posted form. @return array{0:?array,1:?string} [data, error]; data null = no poll requested */
    public static function parse(): array
    {
        if (!self::enabled() || input('poll_enable') !== '1') {
            return [null, null];
        }
        $q = mb_substr(trim((string) input('poll_question')), 0, 200);
        $lines = preg_split('/\R/', (string) ($_POST['poll_options'] ?? '')) ?: [];
        $opts = [];
        foreach ($lines as $l) {
            $l = mb_substr(trim($l), 0, 100);
            if ($l !== '' && !in_array(mb_strtolower($l), array_map('mb_strtolower', $opts), true)) {
                $opts[] = $l;
            }
        }
        if (mb_strlen($q) < 3) {
            return [null, t('Please enter the poll question.')];
        }
        if (count($opts) < 2 || count($opts) > 10) {
            return [null, t('A poll needs 2–10 different options (one per line).')];
        }
        $multiple = input('poll_multiple') === '1';
        $max = $multiple ? max(2, min(count($opts), (int) input('poll_max', count($opts)))) : 1;
        $days = (int) input('poll_days', 0);
        $days = array_key_exists($days, self::DURATIONS) ? $days : 0;
        $res = array_key_exists((string) input('poll_results'), self::RESULTS) ? (string) input('poll_results') : 'after_vote';
        return [['question' => $q, 'options' => $opts, 'multiple' => $multiple ? 1 : 0, 'max' => $max, 'days' => $days, 'results' => $res], null];
    }

    public static function create(int $topicId, array $d): void
    {
        $closes = $d['days'] > 0 ? gmdate('Y-m-d H:i:s', time() + $d['days'] * 86400) : null;
        $pid = db()->insert('INSERT INTO {forum_polls} (topic_id, question, multiple, max_choices, results, closes_at, created_at) VALUES (?,?,?,?,?,?,?)', [$topicId, $d['question'], $d['multiple'], $d['max'], $d['results'], $closes, now()]);
        foreach ($d['options'] as $i => $label) {
            db()->exec('INSERT INTO {forum_poll_options} (poll_id, label, sort_order) VALUES (?,?,?)', [$pid, $label, $i]);
        }
    }

    public static function delete(int $topicId): void
    {
        $pid = (int) db()->val('SELECT id FROM {forum_polls} WHERE topic_id = ?', [$topicId]);
        if ($pid) {
            db()->exec('DELETE FROM {forum_poll_votes} WHERE poll_id = ?', [$pid]);
            db()->exec('DELETE FROM {forum_poll_options} WHERE poll_id = ?', [$pid]);
            db()->exec('DELETE FROM {forum_polls} WHERE id = ?', [$pid]);
        }
    }

    /** Replace the votes of the current user. @return string|null error */
    public static function vote(array $poll, array $topic, int $userId, array $optionIds): ?string
    {
        if (self::isClosed($poll, $topic)) {
            return t('This poll has ended.');
        }
        $valid = array_column(db()->all('SELECT id FROM {forum_poll_options} WHERE poll_id = ?', [$poll['id']]), 'id');
        $ids = array_values(array_unique(array_filter(array_map('intval', $optionIds), static fn($i) => in_array($i, array_map('intval', $valid), true))));
        if (!$ids) {
            return t('Please choose an option.');
        }
        if (count($ids) > (int) $poll['max_choices']) {
            return t('You can choose at most {n} options.', ['n' => $poll['max_choices']]);
        }
        db()->transaction(function () use ($poll, $userId, $ids): void {
            db()->val('SELECT id FROM {forum_polls} WHERE id = ? FOR UPDATE', [$poll['id']]); // serialise parallel requests of the same member
            db()->exec('DELETE FROM {forum_poll_votes} WHERE poll_id = ? AND user_id = ?', [$poll['id'], $userId]);
            foreach ($ids as $i) {
                db()->exec('INSERT INTO {forum_poll_votes} (poll_id, option_id, user_id, created_at) VALUES (?,?,?,?)', [$poll['id'], $i, $userId, now()]);
            }
        });
        return null;
    }
}
