<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Seeds FAQs from config/faq.php (docs/MARKETING_CMS.md).
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_values(config('faq.items', [])) as $index => $item) {
            Faq::query()->updateOrCreate(
                [
                    'category' => $item['category'],
                    'question' => $item['question'],
                ],
                [
                    'answer' => $item['answer'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }
    }
}
