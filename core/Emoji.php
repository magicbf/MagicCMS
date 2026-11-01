<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Emoji + flag library shared by the forum, messages, comments, chat, pages and news.
 *  - picker data (categories, names, keywords) for assets/emoji.js
 *  - render(): turns :shortcodes:, classic text smileys and flag characters into safe markup.
 * Flags are real SVG images (assets/flags/, flag-icons, MIT) because Windows does not draw flag emoji.
 */
final class Emoji
{
    /** category => [label, icon, "emoji keywords…" lines]. The FIRST keyword is the :shortcode:. */
    private const SETS = [
        'smileys' => ['Smileys', '😀', [
            '😀 grinning', '😃 smiley', '😄 smile', '😁 beaming', '😆 laughing', '😅 sweat_smile', '🤣 rofl lol', '😂 joy tears', '🙂 slight_smile', '🙃 upside_down',
            '😉 wink', '😊 blush', '😇 innocent angel', '🥰 smiling_hearts love', '😍 heart_eyes', '🤩 star_struck', '😘 kiss blowing', '😗 kissing', '😚 kissing_closed_eyes', '😋 yum tasty',
            '😛 tongue', '😜 wink_tongue', '🤪 zany crazy', '😝 squint_tongue', '🤑 money_mouth', '🤗 hug hugging', '🤭 hand_over_mouth oops', '🤫 shush quiet', '🤔 thinking hmm', '🤐 zipper_mouth',
            '🤨 raised_eyebrow suspicious', '😐 neutral', '😑 expressionless', '😶 no_mouth speechless', '😏 smirk', '😒 unamused', '🙄 eye_roll', '😬 grimace', '🤥 lying liar', '😌 relieved',
            '😔 pensive', '😪 sleepy', '🤤 drooling', '😴 sleeping zzz', '😷 mask sick', '🤒 thermometer ill', '🤕 bandage hurt', '🤢 nauseated', '🤮 vomit', '🤧 sneeze',
            '🥵 hot', '🥶 cold freezing', '🥴 woozy', '😵 dizzy', '🤯 mind_blown', '🤠 cowboy', '🥳 party partying', '😎 cool sunglasses', '🤓 nerd', '🧐 monocle',
            '😕 confused', '😟 worried', '🙁 frown sad', '☹️ frowning', '😮 open_mouth wow', '😯 hushed', '😲 astonished', '😳 flushed blush_red', '🥺 pleading', '😦 frowning_open',
            '😧 anguished', '😨 fearful', '😰 anxious', '😥 sad_relieved', '😢 cry', '😭 sob', '😱 scream', '😖 confounded', '😣 persevere', '😞 disappointed',
            '😓 sweat', '😩 weary', '😫 tired', '🥱 yawn', '😤 triumph steam', '😡 rage angry', '😠 angry_face mad', '🤬 swearing cursing', '😈 smiling_devil', '👿 imp devil_angry',
            '💀 skull dead', '☠️ skull_bones', '💩 poop', '🤡 clown', '👻 ghost', '👽 alien', '🤖 robot bot', '😺 cat_smile', '😹 cat_joy', '😻 cat_heart_eyes',
        ]],
        'gestures' => ['Gestures', '👍', [
            '👍 thumbsup like yes', '👎 thumbsdown dislike no', '👌 ok_hand', '🤌 pinched_fingers', '✌️ victory peace', '🤞 crossed_fingers luck', '🤟 love_you', '🤘 rock metal horns', '🤙 call_me', '👈 point_left',
            '👉 point_right', '👆 point_up', '👇 point_down', '☝️ index_up', '✋ raised_hand stop', '🤚 raised_back_hand', '🖐️ splayed_hand', '🖖 vulcan', '👋 wave hello bye', '🤝 handshake deal',
            '👏 clap applause', '🙌 raised_hands hooray', '👐 open_hands', '🤲 palms_up', '🙏 pray thanks please', '✊ fist', '👊 punch fist_bump', '🤛 fist_left', '🤜 fist_right', '💪 muscle strong flex',
            '🦾 mechanical_arm', '✍️ writing', '🤳 selfie', '💅 nail_polish', '🫡 salute', '🫶 heart_hands', '🫰 snap_fingers', '🫵 pointing_you', '🧠 brain', '👀 eyes look',
            '👁️ eye', '👂 ear', '👃 nose', '👅 tongue_out', '👄 lips', '🦷 tooth', '🫠 melting', '🫣 peeking', '🫢 gasp', '🫥 dotted_face',
            '🙈 see_no_evil', '🙉 hear_no_evil', '🙊 speak_no_evil', '🤷 shrug', '🤦 facepalm', '🙋 raising_hand', '🙇 bow', '💃 dancer', '🕺 man_dancing', '🏃 running',
        ]],
        'hearts' => ['Hearts & symbols', '❤️', [
            '❤️ heart red_heart', '🧡 orange_heart', '💛 yellow_heart', '💚 green_heart', '💙 blue_heart', '💜 purple_heart', '🖤 black_heart', '🤍 white_heart', '🤎 brown_heart', '💔 broken_heart',
            '❤️‍🔥 heart_on_fire', '💕 two_hearts', '💞 revolving_hearts', '💓 beating_heart', '💗 growing_heart', '💖 sparkling_heart', '💘 cupid', '💝 gift_heart', '💟 heart_decoration', '💋 kiss_mark',
            '💯 hundred 100', '💢 anger', '💥 boom collision', '💫 dizzy_star', '💦 sweat_drops splash', '💨 dash wind', '🕳️ hole', '💬 speech_bubble chat', '💭 thought', '💤 zzz',
            '✅ check done', '❌ cross wrong', '❎ cross_box', '❓ question', '❗ exclamation', '‼️ double_exclamation', '⚠️ warning', '🚫 prohibited banned', '⛔ no_entry', '♻️ recycle',
            '➕ plus', '➖ minus', '✖️ multiply', '➗ divide', '♾️ infinity', '🔞 no_minors', '🆗 ok_button', '🆕 new', '🆒 cool_button', '🔥 fire hot lit',
            '✨ sparkles', '⭐ star', '🌟 glowing_star', '⚡ zap lightning', '🌈 rainbow', '🎉 tada party_popper', '🎊 confetti', '🎈 balloon', '🎁 gift present', '🔔 bell',
        ]],
        'gaming' => ['Gaming & esports', '🎮', [
            '🎮 gamepad controller', '🕹️ joystick', '🖥️ desktop pc', '💻 laptop', '⌨️ keyboard', '🖱️ mouse', '🎧 headphones', '🎤 microphone mic', '📺 tv stream', '📹 camera',
            '🏆 trophy win', '🥇 gold first', '🥈 silver second', '🥉 bronze third', '🏅 medal', '🎖️ military_medal', '👑 crown king', '💎 gem diamond', '⚔️ swords crossed_swords', '🗡️ dagger',
            '🛡️ shield defense', '🏹 bow_arrow archer', '💣 bomb', '🔫 pistol gun', '🪓 axe', '🔪 knife', '🧨 firecracker', '🎯 target dart bullseye', '🎲 dice', '♟️ chess',
            '🧩 puzzle', '🃏 joker', '🎰 slot_machine', '🚀 rocket', '🛸 ufo', '🌍 earth world', '🐺 wolf', '🦅 eagle', '🐉 dragon', '🦁 lion',
            '🐯 tiger', '🦈 shark', '🐍 snake', '🦂 scorpion', '🕷️ spider', '🦇 bat', '🏴‍☠️ pirate_flag', '🚩 red_flag', '🏁 finish checkered', '🏳️ white_flag surrender',
            '⏱️ stopwatch timer', '⌛ hourglass', '📈 chart_up gg', '📉 chart_down', '🔒 lock', '🔓 unlock', '🔑 key', '⚙️ gear settings', '🔧 wrench', '🛠️ tools',
        ]],
        'nature' => ['Animals & nature', '🐶', [
            '🐶 dog', '🐱 cat', '🐭 mouse_face', '🐹 hamster', '🐰 rabbit', '🦊 fox', '🐻 bear', '🐼 panda', '🐨 koala', '🐸 frog',
            '🐵 monkey', '🐔 chicken', '🐧 penguin', '🐦 bird', '🦆 duck', '🦉 owl', '🐴 horse', '🦄 unicorn', '🐝 bee', '🦋 butterfly',
            '🐢 turtle', '🐙 octopus', '🐬 dolphin', '🐳 whale', '🌵 cactus', '🌲 tree', '🌴 palm', '🍀 clover', '🌸 blossom', '🌹 rose',
            '🌻 sunflower', '🌞 sun', '🌙 moon', '☁️ cloud', '🌧️ rain', '❄️ snowflake', '⛄ snowman', '🌊 wave', '🌋 volcano', '🌑 new_moon',
        ]],
        'food' => ['Food & drink', '🍕', [
            '🍕 pizza', '🍔 burger', '🍟 fries', '🌭 hotdog', '🌮 taco', '🌯 burrito', '🍗 chicken_leg', '🥓 bacon', '🍳 egg', '🍞 bread',
            '🍣 sushi', '🍜 ramen noodles', '🍰 cake', '🎂 birthday_cake', '🍩 donut', '🍪 cookie', '🍫 chocolate', '🍿 popcorn', '🍎 apple', '🍌 banana',
            '🍓 strawberry', '🍉 watermelon', '🥑 avocado', '🥕 carrot', '☕ coffee', '🍵 tea', '🥤 soda cup', '🍺 beer', '🍻 cheers beers', '🍷 wine',
            '🥂 champagne', '🍸 cocktail', '🥃 whiskey', '🧃 juice', '🥛 milk', '🍼 bottle', '🧊 ice', '🍽️ plate', '🥄 spoon', '🍴 fork_knife',
        ]],
        'activity' => ['Sports & misc', '⚽', [
            '⚽ soccer football', '🏀 basketball', '🏈 american_football', '⚾ baseball', '🎾 tennis', '🏐 volleyball', '🏉 rugby', '🎱 pool 8ball', '🏓 ping_pong', '🏸 badminton',
            '🥊 boxing', '🥋 martial_arts', '⛳ golf', '🎿 ski', '🏂 snowboard', '🏄 surf', '🚴 cycling', '🏋️ weightlifting', '🤸 cartwheel', '🧘 yoga',
            '🚗 car', '🏎️ race_car', '🏍️ motorcycle', '✈️ airplane', '🚁 helicopter', '🚢 ship', '🚲 bike', '🚦 traffic_light', '🏠 house', '🏢 office',
            '📱 phone', '📷 photo', '📢 loudspeaker announce', '📣 megaphone', '📌 pin', '📎 paperclip', '📝 memo note', '📅 calendar', '📊 bar_chart', '💡 idea bulb',
            '🔗 link', '🔍 search magnifier', '📦 package', '💰 money_bag', '💸 money_wings', '🪙 coin', '💳 credit_card', '🎓 graduation', '📚 books', '🕐 clock',
        ]],
    ];

    /** Classic text smileys → shortcode. Longest first matters, handled in render(). */
    private const TEXT = [
        ':-)' => 'slight_smile', ':)' => 'slight_smile', '(:' => 'slight_smile', ':-D' => 'smile', ':D' => 'smile', 'xD' => 'laughing', 'XD' => 'laughing', ';-)' => 'wink', ';)' => 'wink',
        ':-(' => 'frown', ':(' => 'frown', ":'(" => 'cry', ':-P' => 'tongue', ':P' => 'tongue', ':p' => 'tongue', ':-O' => 'open_mouth', ':O' => 'open_mouth', ':o' => 'open_mouth',
        ':-|' => 'neutral', ':|' => 'neutral', '8)' => 'cool_sunglasses', 'B)' => 'cool_sunglasses', '<3' => 'heart', '</3' => 'broken_heart', ':*' => 'kiss', '>:(' => 'angry_face', ':/' => 'confused',
    ];

    private static ?array $byName = null;
    private static ?array $flags = null;

    /** @return array<string,string> ISO code => country name */
    public static function flags(): array
    {
        return self::$flags ??= (array) require __DIR__ . '/emoji_flags.php';
    }

    private static function index(): array
    {
        if (self::$byName === null) {
            self::$byName = ['cool_sunglasses' => '😎', 'frown' => '🙁', 'kiss' => '😘', 'broken_heart' => '💔', 'heart' => '❤️', 'angry_face' => '😠'];
            foreach (self::SETS as [, , $lines]) {
                foreach ($lines as $l) {
                    [$char, $kw] = explode(' ', $l, 2);
                    foreach (explode(' ', $kw) as $i => $k) {
                        self::$byName[$k] ??= $char;
                    }
                }
            }
        }
        return self::$byName;
    }

    /** Picker payload for assets/emoji.js. */
    public static function picker(): array
    {
        $cats = [];
        foreach (self::SETS as $key => [$label, $icon, $lines]) {
            $items = [];
            foreach ($lines as $l) {
                [$char, $kw] = explode(' ', $l, 2);
                $w = explode(' ', $kw);
                $items[] = [$char, $w[0], str_replace('_', ' ', $kw)];
            }
            $cats[] = ['id' => $key, 'label' => t($label), 'icon' => $icon, 'items' => $items];
        }
        $flags = [];
        foreach (self::flags() as $code => $name) {
            $flags[] = [$code, $name];
        }
        return ['cats' => $cats, 'flags' => $flags, 'flagBase' => url('assets/flags/')];
    }

    /** Unicode flag (two regional indicators) → ISO code in lower case, or null. */
    private static function flagCode(string $pair): ?string
    {
        $cp = array_map('mb_ord', mb_str_split($pair));
        if (count($cp) !== 2 || $cp[0] < 0x1F1E6 || $cp[0] > 0x1F1FF || $cp[1] < 0x1F1E6 || $cp[1] > 0x1F1FF) {
            return null;
        }
        $code = chr($cp[0] - 0x1F1E6 + 97) . chr($cp[1] - 0x1F1E6 + 97);
        return isset(self::flags()[$code]) ? $code : null;
    }

    public static function flagImg(string $code): string
    {
        $name = self::flags()[$code] ?? '';
        $u = mb_chr(0x1F1E6 + ord($code[0]) - 97) . mb_chr(0x1F1E6 + ord($code[1]) - 97);
        if (strlen($code) !== 2) {
            $u = ':flag_' . $code . ':';
        }
        return '<img class="flag" src="' . htmlspecialchars(asset('flags/' . $code . '.svg'), ENT_QUOTES) . '" alt="' . $u . '" title="' . htmlspecialchars($name, ENT_QUOTES) . '" width="20" height="15" loading="lazy">';
    }

    /**
     * Convert smileys in ALREADY ESCAPED / sanitized HTML. Only text between tags is touched, and the
     * output is built from our own tables, so no user input can reach an attribute.
     */
    public static function render(string $html): string
    {
        if ($html === '' || !preg_match('/[:;8BxX<(\x{1F1E6}-\x{1F1FF}]/u', $html)) {
            return $html;
        }
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $inCode = 0;
        foreach ($parts as $i => $p) {
            if ($p === '') {
                continue;
            }
            if ($p[0] === '<') {
                if (preg_match('/^<(pre|code)\b/i', $p)) { $inCode++; }
                elseif (preg_match('/^<\/(pre|code)>/i', $p)) { $inCode = max(0, $inCode - 1); }
                continue;
            }
            if ($inCode) {
                continue;
            }
            $parts[$i] = self::text($p);
        }
        return implode('', $parts);
    }

    private static function text(string $s): string
    {
        $idx = self::index();
        $flags = self::flags();
        $stash = [];   // finished <img> tags are parked behind tokens so later passes can't touch their attributes
        $park = static function (string $img) use (&$stash): string { $stash[] = $img; return "\x02" . (count($stash) - 1) . "\x02"; };
        // :flag_de: and :name:
        $s = preg_replace_callback('/(?<![\w:]):([a-z0-9_+\-]{2,40}):(?![\w])/', static function ($m) use ($idx, $flags, $park) {
            $n = $m[1];
            if (str_starts_with($n, 'flag_')) {
                $c = str_replace('_', '-', substr($n, 5));
                return isset($flags[$c]) ? $park(self::flagImg($c)) : $m[0];
            }
            return $idx[$n] ?? $m[0];
        }, $s) ?? $s;
        // flag characters (two regional indicators)
        $s = preg_replace_callback('/[\x{1F1E6}-\x{1F1FF}]{2}/u', static function ($m) use ($park) {
            $c = self::flagCode($m[0]);
            return $c ? $park(self::flagImg($c)) : $m[0];
        }, $s) ?? $s;
        // classic text smileys (text is already HTML-escaped, so match the escaped forms too)
        static $re = null;
        if ($re === null) {
            $keys = [];
            foreach (array_keys(self::TEXT) as $k) {
                $keys[] = $k;
                $e = htmlspecialchars($k, ENT_QUOTES);
                if ($e !== $k) { $keys[] = $e; }
            }
            usort($keys, static fn($a, $b) => strlen($b) <=> strlen($a));
            $re = '/(?<=^|\s|[>(])(' . implode('|', array_map(static fn($k) => preg_quote($k, '/'), $keys)) . ')(?=$|\s|[<.,!?)])/u';
        }
        $s = preg_replace_callback($re, static function ($m) use ($idx) {
            $k = html_entity_decode($m[1], ENT_QUOTES);
            return isset(self::TEXT[$k]) ? ($idx[self::TEXT[$k]] ?? $m[1]) : $m[1];
        }, $s) ?? $s;
        return $stash ? preg_replace_callback('/\x02(\d+)\x02/', static fn($m) => $stash[(int) $m[1]], $s) : $s;
    }
}
