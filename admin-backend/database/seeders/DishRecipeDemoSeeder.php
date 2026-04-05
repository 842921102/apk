<?php

namespace Database\Seeders;

use App\Models\DishRecipe;
use App\Models\RecommendationSession;
use Illuminate\Database\Seeder;

class DishRecipeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'title' => '番茄鸡蛋面',
                'description' => '家常快手的酸甜口汤面，适合一人食或轻负担晚餐。',
                'ingredients' => ['番茄', '鸡蛋', '面条', '小葱'],
                'seasonings' => ['盐', '糖', '生抽', '番茄酱', '香油'],
                'steps' => [
                    ['content' => '番茄划十字烫皮，切丁；鸡蛋打散；小葱切末。'],
                    ['content' => '热锅少油炒蛋至定型盛出；同锅炒番茄出汁，加少许糖和番茄酱。'],
                    ['content' => '加适量水烧开，下面条煮至合适软硬，调味后倒回鸡蛋拌匀，撒葱花、点香油即可。'],
                ],
                'cooking_time' => '约15分钟',
                'difficulty' => '简单',
                'tips' => ['汤汁略多一点更好拌面；番茄选熟一点的更易出沙。'],
            ],
            [
                'title' => '清蒸鲈鱼',
                'description' => '清淡鲜美，突出鱼肉本味，适合想吃得轻一点的日子。',
                'ingredients' => ['鲈鱼', '姜', '葱'],
                'seasonings' => ['料酒', '盐', '蒸鱼豉油', '植物油'],
                'steps' => [
                    ['content' => '鲈鱼处理干净，两面划刀，用料酒和盐抹匀，盘底垫姜片腌制10分钟。'],
                    ['content' => '水开后上锅大火蒸8～10分钟（视鱼大小），关火焖1分钟。'],
                    ['content' => '倒掉腥水，铺葱丝，淋蒸鱼豉油，热油浇在葱丝上激香即可。'],
                ],
                'cooking_time' => '约25分钟',
                'difficulty' => '中等',
                'tips' => ['蒸久了肉会柴；没有蒸鱼豉油可用生抽+少许糖代替。'],
            ],
        ];

        foreach ($rows as $row) {
            $key = RecommendationSession::dishKey($row['title']);
            DishRecipe::query()->updateOrCreate(
                ['dish_key' => $key],
                array_merge($row, [
                    'dish_key' => $key,
                    'is_active' => true,
                ])
            );
        }
    }
}
