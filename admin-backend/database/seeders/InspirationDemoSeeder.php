<?php

namespace Database\Seeders;

use App\Models\CircleComment;
use App\Models\CirclePost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InspirationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect([
            ['name' => '灵感小厨', 'email' => 'inspiration_01@example.com'],
            ['name' => '阿卓实拍', 'email' => 'inspiration_02@example.com'],
            ['name' => 'AI摆盘官', 'email' => 'inspiration_03@example.com'],
            ['name' => '家常风味', 'email' => 'inspiration_04@example.com'],
            ['name' => '轻食打卡', 'email' => 'inspiration_05@example.com'],
        ])->map(function (array $row): User {
            /** @var User $user */
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => 'password123',
                    'email_verified_at' => now(),
                    'role' => 'user',
                ],
            );
            $user->ensureProfile();

            return $user;
        })->values();

        $titles = [
            '番茄牛腩晚餐灵感', '轻食鸡胸拼盘', '香煎三文鱼摆盘', '奶油蘑菇意面',
            '椒盐虾仁快手菜', '空气炸锅烤鸡翅', '日式咖喱饭', '麻辣香锅家常版',
            '蒜蓉西兰花', '红烧排骨', '香菇滑鸡', '柠檬烤鱼', '牛油果早餐碗',
            '芝士焗土豆', '黑椒牛排', '糖醋里脊', '虾仁炒蛋', '照烧鸡腿饭',
            '家常豆腐', '清汤面暖胃版',
        ];

        $descriptions = [
            '图片为演示数据，适合首页图片流展示。', '偏清淡口味，适合晚餐。', '摆盘突出食材质感，适合收藏。',
            '一锅完成，适合下班后快速开饭。', '留出商品位，后续可挂锅具/食材包。',
        ];

        for ($i = 0; $i < 20; $i++) {
            $user = $users[$i % $users->count()];
            $sourceType = $i % 2 === 0 ? 'ai_generated' : 'user_uploaded';
            $publishSource = $sourceType === 'ai_generated' ? 'ai_result' : 'manual_upload';
            $seed = 500 + $i;
            $images = [
                "https://picsum.photos/seed/wte-insp-{$seed}/960/1280",
                "https://picsum.photos/seed/wte-insp-{$seed}-b/960/1280",
            ];

            $post = CirclePost::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $titles[$i],
                    'publish_source' => $publishSource,
                ],
                [
                    'description' => $descriptions[$i % count($descriptions)],
                    'content' => $descriptions[$i % count($descriptions)],
                    'images' => $images,
                    'cover_image' => $images[0],
                    'source_type' => $sourceType,
                    'publish_source' => $publishSource,
                    'topic' => $sourceType === 'ai_generated' ? 'AI生成' : '用户实拍',
                    'like_count' => random_int(5, 120),
                    'favorite_count' => random_int(2, 80),
                    'comment_count' => 0,
                    'status' => 'normal',
                    'is_recommended' => $i < 8,
                    'is_pinned' => $i < 2,
                    'sort' => 100 - $i,
                    'related_product_id' => $i % 5 === 0 ? ($i + 1000) : null,
                    'published_at' => now()->subHours($i * 2),
                ],
            );

            $commentRows = random_int(1, 3);
            CircleComment::query()->where('post_id', $post->id)->delete();
            for ($c = 0; $c < $commentRows; $c++) {
                $commentUser = $users[($i + $c + 1) % $users->count()];
                CircleComment::query()->create([
                    'post_id' => $post->id,
                    'user_id' => $commentUser->id,
                    'content' => '演示评论 '.Str::upper(Str::random(4)).'：这张图看起来很有食欲。',
                    'status' => 'normal',
                ]);
            }
            $post->comment_count = $commentRows;
            $post->save();
        }

        $this->command?->info('已插入 20 条灵感演示内容。');
    }
}
