<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FruitConfigSeeder extends Seeder
{
    public function run()
    {
        // 权重设计：总分 10000
        // 苹果最容易中，Bar最难中
        $data = [
            ['id'=>1, 'name'=>'BAR',  'multiplier'=>50, 'weight'=>50,   'color_code'=>'c-bar'],   // 0.5%
            ['id'=>2, 'name'=>'77',   'multiplier'=>40, 'weight'=>100,  'color_code'=>'c-77'],    // 1%
            ['id'=>3, 'name'=>'星星',  'multiplier'=>30, 'weight'=>200,  'color_code'=>'c-star'],  // 2%
            ['id'=>4, 'name'=>'西瓜',  'multiplier'=>20, 'weight'=>400,  'color_code'=>'c-water'], // 4%
            ['id'=>5, 'name'=>'铃铛',  'multiplier'=>20, 'weight'=>600,  'color_code'=>'c-bell'],  // 6%
            ['id'=>6, 'name'=>'柠檬',  'multiplier'=>15, 'weight'=>1000, 'color_code'=>'c-lemon'], // 10%
            ['id'=>7, 'name'=>'橘子',  'multiplier'=>10, 'weight'=>2000, 'color_code'=>'c-orange'],// 20%
            ['id'=>8, 'name'=>'苹果',  'multiplier'=>5,  'weight'=>5650, 'color_code'=>'c-apple'], // 56.5%
        ];

        DB::table('fruit_configs')->insert($data);
    }
}
