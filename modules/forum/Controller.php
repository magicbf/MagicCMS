<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Forum;

use MagicCMS\Core\{Auth, Bbcode, Hooks, Router, View};

final class Controller
{
    private function nf(): string
    {
        (new Router())->notFound();
        return '';
    }

    private function deny(): string
    {
        http_response_code(403);
        return View::page(t('Forbidden'), render('message', ['title' => '403', 'text' => t('You do not have permission to do that.')]), ['layout' => 'none']);
    }

    private function page(string $title, string $view, array $data, array $opts = []): string
    {
        return View::page($title, render('forum/' . $view, $data), $opts + ['class' => 'page-forum', 'layout' => 'none']);
    }

    private static function pageNo(int $pages): int
    {
        return max(1, min($pages, (int) input('page', 1)));
    }

    /** Validate title/body; returns error message or null. */
    private function validate(?string $title, string $body): ?string
    {
        if ($title !== null && (mb_strlen($title) < 3 || mb_strlen($title) > 120)) {
            return t('The title must be 3–120 characters.');
        }
        if (mb_strlen(trim($body)) < 2 || mb_strlen($body) > 20000) {
            return t('The message must be 2–20,000 characters.');
        }
        return null;
    }

    // ------------------------------------------------------------------ index
    public function index(): string
    {
        $lvl = Auth::level();
        $cats = db()->all('SELECT * FROM {forum_categories} ORDER BY sort_order, id');
        $boards = db()->all('SELECT * FROM {forum_boards} WHERE read_level <= ? ORDER BY sort_order, id', [$lvl]);

        $last = [];
        $ids = array_filter(array_map('intval', array_column($boards, 'last_post_id')));
        if ($ids) {
            foreach (db()->all(
                'SELECT p.id, p.created_at, p.topic_id, t.title, u.id AS uid, u.username, u.avatar
                 FROM {forum_posts} p JOIN {forum_topics} t ON t.id = p.topic_id JOIN {users} u ON u.id = p.user_id
                 WHERE p.id IN (' . implode(',', $ids) . ')'
            ) as $r) {
                $last[(int) $r['id']] = $r;
            }
        }
        $unread = [];
        if (user()) {
            $params = [user()['id']];
            $expr = Forum::unreadExpr($params);
            foreach (db()->all("SELECT t.board_id, COUNT(*) n FROM {forum_topics} t LEFT JOIN {forum_reads} r ON r.topic_id = t.id AND r.user_id = ? WHERE $expr GROUP BY t.board_id", $params) as $r) {
                $unread[(int) $r['board_id']] = (int) $r['n'];
            }
        }
        $tree = [];
        foreach ($boards as $b) {
            $b['last'] = $last[(int) $b['last_post_id']] ?? null;
            $b['unread'] = $unread[(int) $b['id']] ?? 0;
            if ($b['parent_id']) {
                $tree['sub'][(int) $b['parent_id']][] = $b;
            } else {
                $tree['top'][(int) $b['category_id']][] = $b;
            }
        }
        $stats = [
            'topics' => (int) db()->val('SELECT COUNT(*) FROM {forum_topics}'),
            'posts' => (int) db()->val('SELECT COUNT(*) FROM {forum_posts}'),
            'members' => (int) db()->val("SELECT COUNT(*) FROM {users} WHERE status = 'active'"),
        ];
        return $this->page(t('Forum'), 'index', ['cats' => $cats, 'tree' => $tree, 'stats' => $stats]);
    }

    // ------------------------------------------------------------------ board
    public function board(array $p): string
    {
        $b = Forum::board((int) $p['id']);
        if (!$b || !Forum::canRead($b)) {
            return $this->nf();
        }
        $total = (int) db()->val('SELECT COUNT(*) FROM {forum_topics} WHERE board_id = ?', [$b['id']]);
        $pages = max(1, (int) ceil($total / Forum::PER_PAGE_TOPICS));
        $page = self::pageNo($pages);
        $params = [];
        $unreadExpr = Forum::unreadExpr($params);
        $uid = user()['id'] ?? 0;
        $rows = db()->all(
            "SELECT t.*, u.username AS author, u.id AS author_id, u.avatar AS author_avatar,
                    lp.created_at AS lp_at, lu.username AS lp_user, lu.id AS lp_uid, lu.avatar AS lp_avatar, $unreadExpr AS is_unread
             FROM {forum_topics} t JOIN {users} u ON u.id = t.user_id
             JOIN {forum_posts} lp ON lp.id = t.last_post_id JOIN {users} lu ON lu.id = lp.user_id
             LEFT JOIN {forum_reads} r ON r.topic_id = t.id AND r.user_id = ?
             WHERE t.board_id = ?
             ORDER BY CASE t.type WHEN 'announce' THEN 0 WHEN 'sticky' THEN 1 ELSE 2 END, t.last_post_at DESC, t.id DESC
             LIMIT " . Forum::PER_PAGE_TOPICS . ' OFFSET ' . (($page - 1) * Forum::PER_PAGE_TOPICS),
            array_merge($params, [$uid, $b['id']])
        );
        $subs = db()->all('SELECT * FROM {forum_boards} WHERE parent_id = ? AND read_level <= ? ORDER BY sort_order, id', [$b['id'], Auth::level()]);
        $parent = $b['parent_id'] ? Forum::board((int) $b['parent_id']) : null;
        return $this->page($b['title'], 'board', ['b' => $b, 'rows' => $rows, 'subs' => $subs, 'parent' => $parent, 'page' => $page, 'pages' => $pages]);
    }

    // ------------------------------------------------------------------ topic
    public function topic(array $p): string
    {
        $t = Forum::topic((int) $p['id']);
        if (!$t) {
            return $this->nf();
        }
        $total = (int) $t['reply_count'] + 1;
        $pages = max(1, (int) ceil($total / Forum::PER_PAGE_POSTS));
        $page = self::pageNo($pages);
        $posts = db()->all(
            'SELECT p.*, u.username, u.avatar, u.signature, u.created_at AS joined, u.forum_posts, u.status, r.slug AS role_slug, r.name AS role_name, eb.username AS edited_by_name
             FROM {forum_posts} p JOIN {users} u ON u.id = p.user_id JOIN {roles} r ON r.id = u.role_id LEFT JOIN {users} eb ON eb.id = p.edited_by
             WHERE p.topic_id = ? ORDER BY p.id LIMIT ' . Forum::PER_PAGE_POSTS . ' OFFSET ' . (($page - 1) * Forum::PER_PAGE_POSTS),
            [$t['id']]
        );
        if (empty($_SESSION['fviews'][$t['id']])) {
            $_SESSION['fviews'][$t['id']] = 1;
            db()->exec('UPDATE {forum_topics} SET views = views + 1 WHERE id = ?', [$t['id']]);
        }
        if ($posts && $page === $pages) {
            Forum::markRead((int) $t['id'], (int) $t['last_post_id']);
        } elseif ($posts) {
            Forum::markRead((int) $t['id'], (int) end($posts)['id']);
        }
        $boards = Forum::isMod() ? db()->all('SELECT id, title FROM {forum_boards} WHERE read_level <= ? ORDER BY sort_order, id', [Auth::level()]) : [];
        $parent = $t['board_parent'] ? Forum::board((int) $t['board_parent']) : null;
        return $this->page($t['title'], 'topic', [
            't' => $t, 'posts' => $posts, 'page' => $page, 'pages' => $pages, 'moveTargets' => $boards, 'parent' => $parent,
            'canReply' => Forum::canReply($t),
        ]);
    }

    public function lastPost(array $p): never
    {
        $t = Forum::topic((int) $p['id']);
        $t ? redirect('/forum/post/' . (int) $t['last_post_id']) : redirect('/forum');
    }

    public function jump(array $p): never
    {
        $post = db()->one('SELECT id, topic_id FROM {forum_posts} WHERE id = ?', [(int) $p['id']]);
        if (!$post || !Forum::topic((int) $post['topic_id'])) {
            redirect('/forum');
        }
        $page = Forum::pageOfPost((int) $post['topic_id'], (int) $post['id']);
        redirect('/forum/topic/' . (int) $post['topic_id'] . ($page > 1 ? '?page=' . $page : '') . '#post-' . (int) $post['id']);
    }

    // ------------------------------------------------------------------ posting
    public function newTopic(array $p): string
    {
        $b = Forum::board((int) $p['id']);
        if (!$b || !Forum::canRead($b)) {
            return $this->nf();
        }
        if (!Forum::canPost($b)) {
            return $this->deny();
        }
        $data = ['b' => $b, 'title' => '', 'body' => '', 'mode' => 'new', 'action' => url('forum/board/' . $b['id'] . '/new'), 'canType' => Forum::isMod()];
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $title = (string) input('title');
            $body = (string) ($_POST['body'] ?? '');
            $data['title'] = $title;
            $data['body'] = $body;
            $err = $this->validate($title, $body);
            if (!$err && ($w = Forum::floodWait()) > 0) {
                $err = t('Please wait {n} seconds before posting again.', ['n' => $w]);
            }
            if (!$err) {
                $type = Forum::isMod() && in_array(input('type'), ['sticky', 'announce'], true) ? (string) input('type') : 'normal';
                $now = now();
                $tid = db()->insert('INSERT INTO {forum_topics} (board_id, user_id, title, type, last_post_at, created_at) VALUES (?,?,?,?,?,?)', [$b['id'], user()['id'], $title, $type, $now, $now]);
                $pid = db()->insert('INSERT INTO {forum_posts} (topic_id, user_id, body, created_at) VALUES (?,?,?,?)', [$tid, user()['id'], $body, $now]);
                Forum::syncTopic($tid);
                Forum::syncBoard((int) $b['id']);
                Forum::recountUsers([user()['id']]);
                Forum::markRead($tid, $pid);
                Hooks::fire('forum.topic.created', ['topic_id' => $tid, 'post_id' => $pid, 'board_id' => (int) $b['id'], 'user_id' => (int) user()['id'], 'title' => $title]);
                redirect('/forum/topic/' . $tid);
            }
            http_response_code(422);
            flash('danger', $err);
        }
        return $this->page(t('New topic'), 'form', $data);
    }

    public function reply(array $p): string
    {
        $t = Forum::topic((int) $p['id']);
        if (!$t) {
            return $this->nf();
        }
        if (!Forum::canReply($t)) {
            return $this->deny();
        }
        $body = '';
        if ($qid = (int) input('quote')) { // "Quote" button
            $q = db()->one('SELECT p.body, u.username FROM {forum_posts} p JOIN {users} u ON u.id = p.user_id WHERE p.id = ? AND p.topic_id = ?', [$qid, $t['id']]);
            if ($q) {
                $body = '[quote=' . preg_replace('/[^A-Za-z0-9_\-]/', '', $q['username']) . ']' . trim(preg_replace('/\[quote(?:=[^\]]*)?\].*?\[\/quote\]/is', '', $q['body'])) . "[/quote]\n\n";
            }
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $body = (string) ($_POST['body'] ?? '');
            $err = $this->validate(null, $body);
            if (!$err && ($w = Forum::floodWait()) > 0) {
                $err = t('Please wait {n} seconds before posting again.', ['n' => $w]);
            }
            if (!$err) {
                $pid = db()->insert('INSERT INTO {forum_posts} (topic_id, user_id, body, created_at) VALUES (?,?,?,?)', [$t['id'], user()['id'], $body, now()]);
                Forum::syncTopic((int) $t['id']);
                Forum::syncBoard((int) $t['board_id']);
                Forum::recountUsers([user()['id']]);
                Forum::markRead((int) $t['id'], $pid);
                Hooks::fire('forum.post.created', ['post_id' => $pid, 'topic_id' => (int) $t['id'], 'user_id' => (int) user()['id']]);
                redirect('/forum/post/' . $pid);
            }
            http_response_code(422);
            flash('danger', $err);
        }
        $recent = db()->all('SELECT p.body, u.username FROM {forum_posts} p JOIN {users} u ON u.id = p.user_id WHERE p.topic_id = ? ORDER BY p.id DESC LIMIT 3', [$t['id']]);
        return $this->page(t('Reply'), 'form', ['t' => $t, 'b' => null, 'title' => '', 'body' => $body, 'mode' => 'reply', 'action' => url('forum/topic/' . $t['id'] . '/reply'), 'recent' => $recent, 'canType' => false]);
    }

    private function ownPost(int $id): ?array
    {
        return db()->one('SELECT p.*, t.board_id, t.title, t.first_post_id, t.is_locked AS topic_locked, t.id AS tid FROM {forum_posts} p JOIN {forum_topics} t ON t.id = p.topic_id WHERE p.id = ?', [$id]);
    }

    public function edit(array $p): string
    {
        $post = $this->ownPost((int) $p['id']);
        $t = $post ? Forum::topic((int) $post['topic_id']) : null;
        if (!$post || !$t) {
            return $this->nf();
        }
        $isOwner = (int) $post['user_id'] === (int) user()['id'];
        if (!Forum::isMod() && !($isOwner && !$post['topic_locked'] && can('forum.post'))) {
            return $this->deny();
        }
        $first = (int) $post['id'] === (int) $post['first_post_id'];
        $data = ['t' => $t, 'b' => null, 'post' => $post, 'title' => $first ? $post['title'] : '', 'body' => $post['body'], 'mode' => $first ? 'edit_first' : 'edit', 'action' => url('forum/post/' . $post['id'] . '/edit'), 'canType' => false];
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $title = $first ? (string) input('title') : null;
            $body = (string) ($_POST['body'] ?? '');
            $data['body'] = $body;
            $data['title'] = $title ?? '';
            if ($err = $this->validate($title, $body)) {
                http_response_code(422);
                flash('danger', $err);
            } else {
                db()->exec('UPDATE {forum_posts} SET body = ?, edited_at = ?, edited_by = ? WHERE id = ?', [$body, now(), user()['id'], $post['id']]);
                if ($first) {
                    db()->exec('UPDATE {forum_topics} SET title = ? WHERE id = ?', [$title, $post['topic_id']]);
                }
                if (!$isOwner) {
                    audit('forum.edit', 'post #' . $post['id']);
                }
                redirect('/forum/post/' . $post['id']);
            }
        }
        return $this->page(t('Edit post'), 'form', $data);
    }

    public function deletePost(array $p): never
    {
        $post = $this->ownPost((int) $p['id']);
        $t = $post ? Forum::topic((int) $post['topic_id']) : null;
        if (!$post || !$t) {
            redirect('/forum');
        }
        $first = (int) $post['id'] === (int) $post['first_post_id'];
        $isOwner = (int) $post['user_id'] === (int) user()['id'];
        if (!Forum::isMod() && !($isOwner && !$first && !$post['topic_locked'])) {
            flash('danger', t('You cannot delete this post.'));
            redirect('/forum/post/' . $post['id']);
        }
        if ($first) { // deleting the opening post removes the whole topic
            Forum::deleteTopic((int) $post['topic_id']);
            Forum::syncBoard((int) $post['board_id']);
            audit('forum.delete_topic', '#' . $post['topic_id'] . ' ' . $post['title']);
            flash('success', t('Topic deleted.'));
            redirect('/forum/board/' . (int) $post['board_id']);
        }
        db()->exec('DELETE FROM {forum_reports} WHERE post_id = ?', [$post['id']]);
        db()->exec('DELETE FROM {forum_posts} WHERE id = ?', [$post['id']]);
        Forum::syncTopic((int) $post['topic_id']);
        Forum::syncBoard((int) $post['board_id']);
        Forum::recountUsers([(int) $post['user_id']]);
        if (!$isOwner) {
            audit('forum.delete_post', '#' . $post['id']);
        }
        flash('success', t('Post deleted.'));
        redirect('/forum/topic/' . (int) $post['topic_id'] . '?page=' . Forum::pageOfPost((int) $post['topic_id'], (int) $post['id']));
    }

    // ------------------------------------------------------------------ moderation
    public function mod(array $p): never
    {
        $t = Forum::topic((int) $p['id']);
        if (!$t || !Forum::isMod()) {
            redirect('/forum');
        }
        $id = (int) $t['id'];
        switch ((string) input('action')) {
            case 'lock': db()->exec('UPDATE {forum_topics} SET is_locked = 1 WHERE id = ?', [$id]); break;
            case 'unlock': db()->exec('UPDATE {forum_topics} SET is_locked = 0 WHERE id = ?', [$id]); break;
            case 'sticky': db()->exec("UPDATE {forum_topics} SET type = 'sticky' WHERE id = ?", [$id]); break;
            case 'announce': db()->exec("UPDATE {forum_topics} SET type = 'announce' WHERE id = ?", [$id]); break;
            case 'normal': db()->exec("UPDATE {forum_topics} SET type = 'normal' WHERE id = ?", [$id]); break;
            case 'move':
                $to = Forum::board((int) input('board_id'));
                if ($to && Forum::canRead($to) && (int) $to['id'] !== (int) $t['board_id']) {
                    db()->exec('UPDATE {forum_topics} SET board_id = ? WHERE id = ?', [$to['id'], $id]);
                    Forum::syncBoard((int) $t['board_id']);
                    Forum::syncBoard((int) $to['id']);
                }
                break;
            case 'delete':
                Forum::deleteTopic($id);
                Forum::syncBoard((int) $t['board_id']);
                audit('forum.delete_topic', '#' . $id . ' ' . $t['title']);
                flash('success', t('Topic deleted.'));
                redirect('/forum/board/' . (int) $t['board_id']);
        }
        audit('forum.mod', (string) input('action') . ' topic #' . $id);
        flash('success', t('Done.'));
        redirect('/forum/topic/' . $id);
    }

    public function report(array $p): never
    {
        $post = db()->one('SELECT id, topic_id FROM {forum_posts} WHERE id = ?', [(int) $p['id']]);
        if (!$post || !Forum::topic((int) $post['topic_id']) || !can('forum.report')) {
            redirect('/forum');
        }
        $reason = mb_substr((string) input('reason'), 0, 300);
        if (mb_strlen($reason) < 3) {
            flash('danger', t('Please describe the problem.'));
        } elseif (db()->val("SELECT id FROM {forum_reports} WHERE post_id = ? AND user_id = ? AND status = 'open'", [$post['id'], user()['id']])) {
            flash('info', t('You already reported this post.'));
        } else {
            db()->exec('INSERT INTO {forum_reports} (post_id, user_id, reason, created_at) VALUES (?,?,?,?)', [$post['id'], user()['id'], $reason, now()]);
            flash('success', t('Thank you – the moderators have been notified.'));
        }
        redirect('/forum/post/' . $post['id']);
    }

    public function reports(): string
    {
        $rows = db()->all(
            "SELECT rp.*, p.body, p.topic_id, t.title, u.username AS reporter, au.username AS author, au.id AS author_id
             FROM {forum_reports} rp JOIN {forum_posts} p ON p.id = rp.post_id JOIN {forum_topics} t ON t.id = p.topic_id
             JOIN {users} u ON u.id = rp.user_id JOIN {users} au ON au.id = p.user_id
             WHERE rp.status = 'open' ORDER BY rp.id DESC LIMIT 100"
        );
        return $this->page(t('Reported posts'), 'reports', ['rows' => $rows]);
    }

    public function closeReport(array $p): never
    {
        db()->exec("UPDATE {forum_reports} SET status = 'closed', handled_by = ? WHERE id = ?", [user()['id'], (int) $p['id']]);
        flash('success', t('Report closed.'));
        redirect('/forum/reports');
    }

    // ------------------------------------------------------------------ search / unread / preview
    public function search(): string
    {
        $q = mb_substr((string) input('q', ''), 0, 100);
        $rows = [];
        if (mb_strlen($q) >= 3) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $rows = db()->all(
                'SELECT p.id, p.body, p.created_at, t.id AS tid, t.title, b.title AS board, u.username, u.id AS uid,
                        MATCH(p.body) AGAINST (? IN NATURAL LANGUAGE MODE) AS score
                 FROM {forum_posts} p JOIN {forum_topics} t ON t.id = p.topic_id JOIN {forum_boards} b ON b.id = t.board_id JOIN {users} u ON u.id = p.user_id
                 WHERE b.read_level <= ? AND (MATCH(p.body) AGAINST (? IN NATURAL LANGUAGE MODE) OR t.title LIKE ?)
                 ORDER BY score DESC, p.id DESC LIMIT 50',
                [$q, Auth::level(), $q, $like]
            );
        }
        return $this->page(t('Search'), 'search', ['q' => $q, 'rows' => $rows]);
    }

    public function unread(): string
    {
        $params = [];
        $expr = Forum::unreadExpr($params);
        $rows = db()->all(
            "SELECT t.*, b.title AS board_title, u.username AS author, u.id AS author_id, u.avatar AS author_avatar,
                    lp.created_at AS lp_at, lu.username AS lp_user, lu.id AS lp_uid, lu.avatar AS lp_avatar, 1 AS is_unread
             FROM {forum_topics} t JOIN {forum_boards} b ON b.id = t.board_id JOIN {users} u ON u.id = t.user_id
             JOIN {forum_posts} lp ON lp.id = t.last_post_id JOIN {users} lu ON lu.id = lp.user_id
             LEFT JOIN {forum_reads} r ON r.topic_id = t.id AND r.user_id = ?
             WHERE b.read_level <= ? AND $expr ORDER BY t.last_post_at DESC LIMIT 50",
            array_merge([user()['id'], Auth::level()], $params)
        );
        return $this->page(t('Unread topics'), 'unread', ['rows' => $rows]);
    }

    public function markRead(): never
    {
        db()->exec('INSERT INTO {forum_markread} (user_id, marked_at) VALUES (?,?) ON DUPLICATE KEY UPDATE marked_at = VALUES(marked_at)', [user()['id'], now()]);
        db()->exec('DELETE FROM {forum_reads} WHERE user_id = ?', [user()['id']]);
        flash('success', t('All topics marked as read.'));
        redirect('/forum');
    }

    public function preview(): never
    {
        json_out(['ok' => true, 'html' => Bbcode::render(mb_substr((string) ($_POST['body'] ?? ''), 0, 20000))]);
    }
}
