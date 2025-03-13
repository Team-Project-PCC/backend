<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\PromotionRules;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promotions = [
            ['code' => 'GITARUPA50', 'type' => 'fixed_discount', 'value' => 50000, 'valid_from' => '2025-01-01', 'valid_until' => '2025-12-31', 'max_usage' => 500, 'is_active' => true],
            ['code' => 'HARMONI10', 'type' => 'percentage', 'value' => 10, 'valid_from' => '2025-03-01', 'valid_until' => '2025-03-15', 'max_usage' => 300, 'is_active' => true],
            ['code' => 'FESTIVAL20', 'type' => 'percentage', 'value' => 20, 'valid_from' => '2025-04-10', 'valid_until' => '2025-04-30', 'max_usage' => 200, 'is_active' => true],
            ['code' => 'MELODI25', 'type' => 'percentage', 'value' => 25, 'valid_from' => '2025-06-01', 'valid_until' => '2025-06-07', 'max_usage' => 150, 'is_active' => true],
            ['code' => 'GITARHERO12', 'type' => 'fixed_discount', 'value' => 120000, 'valid_from' => '2025-12-10', 'valid_until' => '2025-12-12', 'max_usage' => 500, 'is_active' => true],
            ['code' => 'MUSIKGRATIS75', 'type' => 'fixed_discount', 'value' => 75000, 'valid_from' => '2025-07-01', 'valid_until' => '2025-07-31', 'max_usage' => 400, 'is_active' => true],
            ['code' => 'JAMSESSION50', 'type' => 'fixed_discount', 'value' => 50000, 'valid_from' => '2025-08-25', 'valid_until' => '2025-08-31', 'max_usage' => 250, 'is_active' => true],
            ['code' => 'BACKTOMUSEUM15', 'type' => 'percentage', 'value' => 15, 'valid_from' => '2025-09-01', 'valid_until' => '2025-09-07', 'max_usage' => 300, 'is_active' => true],
            ['code' => 'WINTERJAZZ30', 'type' => 'percentage', 'value' => 30, 'valid_from' => '2025-11-15', 'valid_until' => '2025-11-30', 'max_usage' => 200, 'is_active' => true],
            ['code' => 'YEAR-ENDMUSIC100', 'type' => 'fixed_discount', 'value' => 100000, 'valid_from' => '2025-12-20', 'valid_until' => '2025-12-31', 'max_usage' => 350, 'is_active' => true],
        ];

        foreach ($promotions as $promoData) {
            $promotion = Promotion::create([
                'code' => $promoData['code'],
                'type' => $promoData['type'],
                'value' => $promoData['value'],
                'valid_from' => Carbon::parse($promoData['valid_from']),
                'valid_until' => Carbon::parse($promoData['valid_until']),
                'max_usage' => $promoData['max_usage'],
                'is_active' => $promoData['is_active'],
            ]);

            PromotionRules::create([
                'promotion_id' => $promotion->id,
                'rule_type' => 'max_discount',
                'rule_value' => 150000,
            ]);

            PromotionRules::create([
                'promotion_id' => $promotion->id,
                'rule_type' => 'min_order',
                'rule_value' => 200000,
            ]);
        }
    }
}
