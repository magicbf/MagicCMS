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
        return View::page($title, render('forum/' . $view, $data), $opts + ['class' => 'page-forum', 'layout' => Forum::layout()]);
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

    // ------------------------------------------------------------------ index / category
    /** @return array{cats:array,tree:array} categories with their readable subcategories */
    private function structure(?int $onlyCategory = null): array
    {
        $lvl = Auth::level();
        $cats = db()->all('SELECT * FROM {forum_categories}' . ($onlyCategory ? ' WHERE id = ' . (int) $onlyCategory : '') . ' ORDER BY sort_order, id');
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
            $tree[(int) $b['category_id']][] = $b;
        }
        return ['cats' => $cats, 'tree' => $tree];
    }

    public function index(): string
    {
        $stats = [
            'topics' => (int) db()->val('SELECT COUNT(*) FROM {forum_topics}'),
            'posts' => (int) db()->val('SELECT COUNT(*) FROM {forum_posts}'),
            'members' => (int) db()->val("SELECT COUNT(*) FROM {users} WHERE status = 'active'"),
        ];
        return $this->page(t('Forum'), 'index', $this->structure() + ['stats' => $stats, 'focus' => null]);
    }

    public function category(array $p): string
    {
        $data = $this->structure((int) $p['id']);
        if (!$data['cats'] || !($data['tree'][(int) $p['id']] ?? null)) {
            return $this->nf();
        }
        return $this->page($data['cats'][0]['title'], 'index', $data + ['stats' => null, 'focus' => $data['cats'][0]]);
    }

    public function legacyBoard(array $p): never
    {
        redirect('/forum/subcategory/' . (int) $p['id'], 301);
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
                    lp.created_at AS lp_at, lu.username AS lp_user, lu.id AS lp_uid, lu.avatar AS lp_avatar, $unreadExpr AS is_unread,
                    EXISTS(SELECT 1 FROM {forum_polls} fp WHERE fp.topic_id = t.id) AS has_poll,
                    EXISTS(SELECT 1 FROM {forum_attachments} fa WHERE fa.post_id = t.first_post_id) AS has_att
             FROM {forum_topics} t JOIN {users} u ON u.id = t.user_id
             JOIN {forum_posts} lp ON lp.id = t.last_post_id JOIN {users} lu ON lu.id = lp.user_id
             LEFT JOIN {forum_reads} r ON r.topic_id = t.id AND r.user_id = ?
             WHERE t.board_id = ?
             ORDER BY CASE t.type WHEN 'announce' THEN 0 WHEN 'sticky' THEN 1 ELSE 2 END, t.last_post_at DESC, t.id DESC
             LIMIT " . Forum::PER_PAGE_TOPICS . ' OFFSET ' . (($page - 1) * Forum::PER_PAGE_TOPICS),
            array_merge($params, [$uid, $b['id']])
        );
        return $this->page($b['title'], 'board', ['b' => $b, 'rows' => $rows, 'page' => $page, 'pages' => $pages, 'subscribed' => user() ? Subscriptions::isBoard((int) user()['id'], (int) $b['id']) : false]);
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
            'SELECT p.*, u.username, u.avatar, u.signature, u.created_at AS joined, u.forum_posts, u.status, r.slug AS role_slug, r.name AS role_name, r.level AS role_level, eb.username AS edited_by_name
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
        $ids = array_column($posts, 'id');
        $extras = ['atts' => Attachments::forPosts($ids), 'reacts' => Reactions::enabled() ? Reactions::forPosts($ids) : [], 'poll' => Polls::forTopic((int) $t['id'])];
        $subscribed = false;
        if (user()) {
            $subscribed = Subscriptions::isTopic((int) user()['id'], (int) $t['id']);
            if ($subscribed) {
                Subscriptions::seen((int) user()['id'], (int) $t['id']);
            }
        }
        $boards = Forum::isMod() ? db()->all('SELECT b.id, CONCAT(c.title, \' › \', b.title) AS title FROM {forum_boards} b JOIN {forum_categories} c ON c.id = b.category_id WHERE b.read_level <= ? ORDER BY c.sort_order, c.id, b.sort_order, b.id', [Auth::level()]) : [];
        return $this->page($t['title'], 'topic', [
            't' => $t, 'posts' => $posts, 'page' => $page, 'pages' => $pages, 'moveTargets' => $boards,
            'canReply' => Forum::canReply($t), 'subscribed' => $subscribed,
        ] + $extras);
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
        $data = ['b' => $b, 'title' => '', 'body' => '', 'mode' => 'new', 'action' => url('forum/subcategory/' . $b['id'] . '/new'), 'canType' => Forum::isMod()];
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $title = (string) input('title');
            $body = (string) ($_POST['body'] ?? '');
            $data['title'] = $title;
            $data['body'] = $body;
            $err = $this->validate($title, $body);
            [$poll, $perr] = Polls::parse();
            $err = $err ?: $perr;
            if (!$err && ($w = Forum::floodWait()) > 0) {
                $err = t('Please wait {n} seconds before posting again.', ['n' => $w]);
            }
            if (!$err) {
                $type = Forum::isMod() && in_array(input('type'), ['sticky', 'announce'], true) ? (string) input('type') : 'normal';
                $now = now();
                $tid = db()->insert('INSERT INTO {forum_topics} (board_id, user_id, title, type, last_post_at, created_at) VALUES (?,?,?,?,?,?)', [$b['id'], user()['id'], $title, $type, $now, $now]);
                $pid = db()->insert('INSERT INTO {forum_posts} (topic_id, user_id, body, created_at) VALUES (?,?,?,?)', [$tid, user()['id'], $body, $now]);
                if ($poll) {
                    Polls::create($tid, $poll);
                }
                $this->attach($pid);
                Forum::syncTopic($tid);
                Forum::syncBoard((int) $b['id']);
                Forum::recountUsers([user()['id']]);
                Forum::markRead($tid, $pid);
                Subscriptions::autoSubscribe($tid);
                Subscriptions::onTopic($b, $tid, $title, (int) user()['id'], $body);
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
                $this->attach($pid);
                Forum::syncTopic((int) $t['id']);
                Forum::syncBoard((int) $t['board_id']);
                Forum::recountUsers([user()['id']]);
                Forum::markRead((int) $t['id'], $pid);
                Subscriptions::autoSubscribe((int) $t['id']);
                Subscriptions::onReply($t, $pid, (int) user()['id'], $body);
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
        if (!$post || !$t || !Forum::canRead($t)) {
            return $this->nf();
        }
        $isOwner = (int) $post['user_id'] === (int) user()['id'];
        $modOk = Forum::isMod() && Auth::mayAffect((int) $post['user_id']);
        if (!$modOk && !($isOwner && !$post['topic_locked'] && can('forum.post'))) {
            return $this->deny();
        }
        $first = (int) $post['id'] === (int) $post['first_post_id'];
        $poll = $first && Polls::enabled() ? Polls::forTopic((int) $post['topic_id']) : null;
        $data = ['t' => $t, 'b' => null, 'post' => $post, 'title' => $first ? $post['title'] : '', 'body' => $post['body'], 'mode' => $first ? 'edit_first' : 'edit', 'action' => url('forum/post/' . $post['id'] . '/edit'), 'canType' => false,
                 'poll' => $poll, 'pollLocked' => $poll && $poll['voters'] > 0, 'atts' => Attachments::forPosts([(int) $post['id']])[(int) $post['id']] ?? []];
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $title = $first ? (string) input('title') : null;
            $body = (string) ($_POST['body'] ?? '');
            $data['body'] = $body;
            $data['title'] = $title ?? '';
            $err = $this->validate($title, $body);
            $np = null;
            $editPoll = $first && Polls::enabled() && !$data['pollLocked'];
            if (!$err && $editPoll) {
                [$np, $perr] = Polls::parse();
                $err = $perr;
            }
            if ($err) {
                http_response_code(422);
                flash('danger', $err);
            } else {
                if ($editPoll) {
                    Polls::delete((int) $post['topic_id']);
                    if ($np) {
                        Polls::create((int) $post['topic_id'], $np);
                    }
                }
                foreach ((array) ($_POST['remove_att'] ?? []) as $aid) {
                    Attachments::deleteOne((int) $aid, (int) $post['id']);
                }
                $this->attach((int) $post['id']);
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
        if (!$post || !$t || !Forum::canRead($t)) {
            redirect('/forum');
        }
        $first = (int) $post['id'] === (int) $post['first_post_id'];
        $isOwner = (int) $post['user_id'] === (int) user()['id'];
        $modOk = Forum::isMod() && Auth::mayAffect((int) $post['user_id']);
        if (!$modOk && !($isOwner && !$first && !$post['topic_locked'])) {
            flash('danger', t('You cannot delete this post.'));
            redirect('/forum/post/' . $post['id']);
        }
        if ($first) { // deleting the opening post removes the whole topic
            Forum::deleteTopic((int) $post['topic_id']);
            Forum::syncBoard((int) $post['board_id']);
            audit('forum.delete_topic', '#' . $post['topic_id'] . ' ' . $post['title']);
            flash('success', t('Topic deleted.'));
            redirect('/forum/subcategory/' . (int) $post['board_id']);
        }
        db()->exec('DELETE FROM {forum_reports} WHERE post_id = ?', [$post['id']]);
        Attachments::deleteForPosts([(int) $post['id']]);
        Reactions::deleteForPosts([(int) $post['id']]);
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
        if (!$t || !Forum::canRead($t) || !Forum::isMod() || !Auth::mayAffect((int) $t['user_id'], (int) $t['author_level'])) {
            flash('danger', t('You cannot moderate this topic.'));
            redirect($t ? '/forum/topic/' . (int) $t['id'] : '/forum');
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
                redirect('/forum/subcategory/' . (int) $t['board_id']);
        }
        audit('forum.mod', (string) input('action') . ' topic #' . $id);
        flash('success', t('Done.'));
        redirect('/forum/topic/' . $id);
    }

    public function report(array $p): never
    {
        $post = db()->one('SELECT id, topic_id FROM {forum_posts} WHERE id = ?', [(int) $p['id']]);
        $rt = $post ? Forum::topic((int) $post['topic_id']) : null;
        if (!$post || !$rt || !Forum::canRead($rt) || !can('forum.report')) {
            redirect('/forum');
        }
        $reason = mb_substr((string) input('reason'), 0, 300);
        if (mb_strlen($reason) < 3) {
            flash('danger', t('Please describe the problem.'));
        } elseif (db()->val("SELECT id FROM {forum_reports} WHERE post_id = ? AND user_id = ? AND status = 'open'", [$post['id'], user()['id']])) {
            flash('info', t('You already reported this post.'));
        } else {
            db()->exec('INSERT INTO {forum_reports} (post_id, user_id, reason, created_at) VALUES (?,?,?,?)', [$post['id'], user()['id'], $reason, now()]);
            \MagicCMS\Modules\Notifications\Notifications::addForPermission('forum.moderate', 'report', t('Post reported by {who}', ['who' => user()['username']]), '/forum/reports', mb_substr($reason, 0, 120), 'report:' . $post['id'], (int) user()['id']);
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

    // ------------------------------------------------------------------ subscriptions
    public function subscribeTopic(array $p): never
    {
        $t = Forum::topic((int) $p['id']);
        if ($t && !Forum::canRead($t)) {
            $t = null;
        }
        if ($t) {
            $on = input('sub') === '1';
            Subscriptions::setTopic((int) user()['id'], (int) $t['id'], $on);
            flash('success', $on ? t('You now follow this topic and will be notified about new replies.') : t('You no longer follow this topic.'));
        }
        redirect($t ? '/forum/topic/' . (int) $t['id'] : '/forum');
    }

    public function subscribeBoard(array $p): never
    {
        $b = Forum::board((int) $p['id']);
        if ($b && Forum::canRead($b)) {
            $on = input('sub') === '1';
            Subscriptions::setBoard((int) user()['id'], (int) $b['id'], $on);
            flash('success', $on ? t('You will be notified about new topics here.') : t('Notifications for this subcategory are off.'));
        }
        redirect($b ? '/forum/subcategory/' . (int) $b['id'] : '/forum');
    }

    public function subscriptions(): string
    {
        $uid = (int) user()['id'];
        $topics = db()->all(
            'SELECT t.id, t.title, t.reply_count, t.last_post_at, b.title AS board_title, b.read_level FROM {forum_topic_subs} s JOIN {forum_topics} t ON t.id = s.topic_id JOIN {forum_boards} b ON b.id = t.board_id
             WHERE s.user_id = ? AND b.read_level <= ? ORDER BY t.last_post_at DESC LIMIT 200',
            [$uid, Auth::level()]
        );
        $boards = db()->all(
            'SELECT b.id, b.title, c.title AS category_title FROM {forum_board_subs} s JOIN {forum_boards} b ON b.id = s.board_id JOIN {forum_categories} c ON c.id = b.category_id
             WHERE s.user_id = ? AND b.read_level <= ? ORDER BY c.sort_order, b.sort_order',
            [$uid, Auth::level()]
        );
        return $this->page(t('My subscriptions'), 'subscriptions', ['topics' => $topics, 'boards' => $boards]);
    }

    /** One-click unsubscribe from an e-mail (signed link, works without login). */
    public function unsubForm(array $p): string
    {
        if (!hash_equals(Subscriptions::sig($p['kind'], (int) $p['uid'], (int) $p['id']), $p['sig'])) {
            return $this->nf();
        }
        $title = $p['kind'] === 'topic' ? (string) db()->val('SELECT title FROM {forum_topics} WHERE id = ?', [(int) $p['id']]) : (string) db()->val('SELECT title FROM {forum_boards} WHERE id = ?', [(int) $p['id']]);
        return $this->page(t('Unsubscribe'), 'unsub', ['p' => $p, 'title' => $title]);
    }

    public function unsub(array $p): never
    {
        if (hash_equals(Subscriptions::sig($p['kind'], (int) $p['uid'], (int) $p['id']), $p['sig'])) {
            $p['kind'] === 'topic' ? Subscriptions::setTopic((int) $p['uid'], (int) $p['id'], false) : Subscriptions::setBoard((int) $p['uid'], (int) $p['id'], false);
            flash('success', t('You have been unsubscribed.'));
        }
        redirect(user() ? '/forum/subscriptions' : '/forum');
    }

    // ------------------------------------------------------------------ attachments / polls / reactions
    /** Store uploaded files for a post; problems are reported as a flash message (the post itself is kept). */
    private function attach(int $postId): void
    {
        $errors = Attachments::save($postId, Attachments::normalize($_FILES['files'] ?? null));
        if ($errors) {
            flash('danger', t('Your post was saved, but some attachments were rejected:') . ' ' . implode(' ', $errors));
        }
    }

    public function attachment(array $p): string
    {
        $a = db()->one('SELECT a.*, p.topic_id FROM {forum_attachments} a JOIN {forum_posts} p ON p.id = a.post_id WHERE a.id = ?', [(int) $p['id']]);
        $t = $a ? Forum::topic((int) $a['topic_id']) : null;
        if (!$a || !$t || !Forum::canRead($t)) {
            return $this->nf();
        }
        $thumb = input('thumb') === '1' && $a['thumb'];
        $name = (string) ($thumb ? $a['thumb'] : $a['stored']);
        $file = Attachments::dir() . '/' . $name;
        if (!preg_match('/^(att|thm|file)_[a-f0-9]{12}(\.(webp|jpg))?$/', $name) || !is_file($file)) {
            return $this->nf();
        }
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('X-Content-Type-Options: nosniff');
        if ($a['is_image']) {
            header('Content-Type: ' . (str_ends_with($name, '.jpg') ? 'image/jpeg' : 'image/webp'));
            header('Cache-Control: private, max-age=86400');
        } else {
            db()->exec('UPDATE {forum_attachments} SET downloads = downloads + 1 WHERE id = ?', [$a['id']]);
            $fn = preg_replace('/[^A-Za-z0-9._\- ]/', '_', $a['orig_name']);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fn . '"; filename*=UTF-8\'\'' . rawurlencode($a['orig_name']));
            header("Content-Security-Policy: default-src 'none'; sandbox");
        }
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    private function pollContext(array $p): array
    {
        $t = Forum::topic((int) $p['id']);
        $poll = $t ? Polls::forTopic((int) $t['id']) : null;
        if (!$t || !$poll || !Forum::canRead($t)) {
            flash('danger', t('Poll not found.'));
            redirect('/forum');
        }
        return [$t, $poll];
    }

    public function vote(array $p): never
    {
        [$t, $poll] = $this->pollContext($p);
        if (!can('forum.vote')) {
            flash('danger', t('You cannot vote.'));
        } else {
            $sel = $_POST['opt'] ?? [];
            $err = Polls::vote($poll, $t, (int) user()['id'], is_array($sel) ? $sel : [$sel]);
            flash($err ? 'danger' : 'success', $err ?: t('Your vote has been saved.'));
        }
        redirect('/forum/topic/' . (int) $t['id'] . '#poll');
    }

    public function pollClose(array $p): never
    {
        [$t, $poll] = $this->pollContext($p);
        if ((int) $t['user_id'] === (int) user()['id'] || (Forum::isMod() && Auth::mayAffect((int) $t['user_id'], (int) $t['author_level']))) {
            db()->exec('UPDATE {forum_polls} SET is_closed = ? WHERE id = ?', [$poll['is_closed'] ? 0 : 1, $poll['id']]);
            if (!$poll['is_closed'] && $poll['closes_at'] && strtotime($poll['closes_at'] . ' UTC') <= time()) {
                db()->exec('UPDATE {forum_polls} SET closes_at = NULL WHERE id = ?', [$poll['id']]);
            }
        }
        redirect('/forum/topic/' . (int) $t['id'] . '#poll');
    }

    public function pollDelete(array $p): never
    {
        [$t, $poll] = $this->pollContext($p);
        $mod = Forum::isMod() && Auth::mayAffect((int) $t['user_id'], (int) $t['author_level']);
        if ($mod || ((int) $t['user_id'] === (int) user()['id'] && $poll['voters'] === 0)) {
            Polls::delete((int) $t['id']);
            if ($mod && (int) $t['user_id'] !== (int) user()['id']) {
                audit('forum.poll_delete', 'topic #' . $t['id']);
            }
            flash('success', t('Poll removed.'));
        }
        redirect('/forum/topic/' . (int) $t['id']);
    }

    public function react(array $p): never
    {
        $post = db()->one('SELECT id, user_id, topic_id FROM {forum_posts} WHERE id = ?', [(int) $p['id']]);
        $t = $post ? Forum::topic((int) $post['topic_id']) : null;
        if (!$t || !Forum::canRead($t) || !Reactions::enabled() || !can('forum.react') || (int) $post['user_id'] === (int) user()['id']) {
            flash('danger', t('You cannot react to this post.'));
            redirect($post ? '/forum/post/' . (int) $post['id'] : '/forum');
        }
        $kind = Reactions::toggle((int) $post['id'], (int) user()['id'], (string) input('kind'));
        if ($kind) {
            notify_user((int) $post['user_id'], 'forum_react', t('{who} reacted {emoji} to your post in “{title}”', ['who' => user()['username'], 'emoji' => Reactions::KINDS[$kind], 'title' => $t['title']]), '/forum/post/' . (int) $post['id'], '', 'react:' . $post['id']);
        }
        redirect('/forum/post/' . (int) $post['id']);
    }
}
