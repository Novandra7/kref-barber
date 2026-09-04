<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('services')->insert([
            // Haircut
            [
                'name' => 'Regular Haircut',
                'category' => 'Haircut',
                'price' => 70000,
                'description' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Long Trim',
                'category' => 'Haircut',
                'price' => 80000,
                'description' => 'https://www.instagram.com/s/aGlnaGxpZ2h0OjE3OTk2NzYwMjI3OTUwNDA1?story_media_id=3811881980460778552&igsi=MW85enZmYmh1YW0wNw==',
                'is_active' => true,
            ],

            // Chemicals
            [
                'name' => 'Design Perm',
                'category' => 'Chemicals',
                'price' => 300000,
                'description' => 'https://www.instagram.com/s/aGlnaGxpZ2h0OjE4NDM5ODMyMzg2MTAwNjI2?story_media_id=3808496042430895460&igsi=MTYwbTRsem55cDhoMQ==',
                'is_active' => true,
            ],
            [
                'name' => 'Root Lift',
                'category' => 'Chemicals',
                'price' => 100000,
                'description' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Perming',
                'category' => 'Chemicals',
                'price' => 250000,
                'description' => 'https://www.instagram.com/reel/DZmen2fzMCj/?igsi=eGk0bHprYW0yYTB1',
                'is_active' => true,
            ],
            [
                'name' => 'Down Perm',
                'category' => 'Chemicals',
                'price' => 150000,
                'description' => 'https://www.instagram.com/s/aGlnaGxpZ2h0OjE4MTIzMTg0MjM0NTQ1ODIx?story_media_id=3801158354283479263&igsi=dWp0NG0xNHBtZzU1',
                'is_active' => true,
            ],
            [
                'name' => 'Fashion Color',
                'category' => 'Chemicals',
                'price' => 350000,
                'description' => 'https://www.instagram.com/reel/DZ6ofD5TDc0/?igsi=MWF4amJnZ2xhanF1MA==',
                'is_active' => true,
            ],
            [
                'name' => 'Highlight',
                'category' => 'Chemicals',
                'price' => 250000,
                'description' => 'https://www.instagram.com/s/aGlnaGxpZ2h0OjE4MDU3OTQ2NjQzMzU1OTgy?story_media_id=3824281106993352447&igsi=MjlmMDN3ZG94aHNt',
                'is_active' => true,
            ],
            [
                'name' => 'Toning',
                'category' => 'Chemicals',
                'price' => 130000,
                'description' => 'https://www.instagram.com/s/aGlnaGxpZ2h0OjE3ODYyMzk4Nzk2NTkzOTEx?story_media_id=3906968852781804021&igsi=MW93Zm1xNDRmdWV1dA==',
                'is_active' => true,
            ],

            // Treatment
            [
                'name' => 'Hairwash',
                'category' => 'Treatment',
                'price' => 50000,
                'description' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Styling',
                'category' => 'Treatment',
                'price' => 50000,
                'description' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Face Mask',
                'category' => 'Treatment',
                'price' => 50000,
                'description' => 'https://www.instagram.com/s/aGlnaGxpZ2h0OjE4MzcyODU1MzY3MDkzNzI3?story_media_id=3928570881010560480&igsi=MW12eTJ5a2g0OHFhaA==',
                'is_active' => true,
            ],
            [
                'name' => 'Hair Mask',
                'category' => 'Treatment',
                'price' => 50000,
                'description' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Creambath',
                'category' => 'Treatment',
                'price' => 50000,
                'description' => 'https://www.instagram.com/s/aGlnaGxpZ2h0OjE4MDUwNjY3NDk0NDgyMjA4?story_media_id=3809823444998289502&igsi=N2Q0NjZxbzh0cjZv',
                'is_active' => true,
            ],
            [
                'name' => 'Scalp Scrub',
                'category' => 'Treatment',
                'price' => 100000,
                'description' => 'https://www.instagram.com/reel/Da4xhHJzpYJ/?igsi=MTNqdGNtODNkOWhwcg==',
                'is_active' => true,
            ],
        ]);
    }
}