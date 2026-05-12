<?php

namespace Database\Seeders;

use App\Models\Dealer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 30 Kenyan agro-dealers seeded for P4 launch.
 *
 * Mix of national chains (Elgon Kenya, ETG, Juanco SPS, Twiga Chemicals)
 * and county-level shops in Meru + Kirinyaga + Nyeri (the JAICA pilot
 * counties). GPS coordinates approximated from the dealer's main town —
 * exact coordinates are out of scope until the dealer onboarding flow
 * lands in P5.
 *
 * Idempotent on `slug` — re-running updates rather than duplicates.
 */
class DealerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->dealers() as $row) {
            $slug = Str::slug($row['name']);
            Dealer::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($row, ['slug' => $slug])
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dealers(): array
    {
        $all = ['seed', 'fertiliser', 'chemical', 'equipment'];
        $seedFert = ['seed', 'fertiliser'];
        $fertChem = ['fertiliser', 'chemical'];

        return [
            // National chains
            ['name' => 'Elgon Kenya — Industrial Area', 'county' => 'Nairobi', 'town' => 'Nairobi', 'gps_lat' => -1.3142, 'gps_lng' => 36.8502, 'phone' => '+254 722 200200', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'ETG Inputs — Nairobi', 'county' => 'Nairobi', 'town' => 'Nairobi', 'gps_lat' => -1.2864, 'gps_lng' => 36.8172, 'phone' => '+254 711 023000', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Juanco SPS — Nairobi', 'county' => 'Nairobi', 'town' => 'Nairobi', 'gps_lat' => -1.3032, 'gps_lng' => 36.8245, 'phone' => '+254 722 506303', 'stocks' => $fertChem, 'is_pcpb_certified' => true],
            ['name' => 'Twiga Chemicals — Industrial Area', 'county' => 'Nairobi', 'town' => 'Nairobi', 'gps_lat' => -1.3150, 'gps_lng' => 36.8480, 'phone' => '+254 711 100100', 'stocks' => ['chemical', 'fertiliser'], 'is_pcpb_certified' => true],
            ['name' => 'Kenya Seed Company — Westlands', 'county' => 'Nairobi', 'town' => 'Nairobi', 'gps_lat' => -1.2640, 'gps_lng' => 36.8033, 'phone' => '+254 722 207300', 'stocks' => ['seed'], 'is_pcpb_certified' => false],
            ['name' => 'Simlaw Seeds — Karen', 'county' => 'Nairobi', 'town' => 'Karen', 'gps_lat' => -1.3255, 'gps_lng' => 36.7068, 'phone' => '+254 723 234567', 'stocks' => ['seed'], 'is_pcpb_certified' => false],

            // Meru — JAICA pilot county
            ['name' => 'Meru Farmers Agrovet — Town', 'county' => 'Meru', 'town' => 'Meru', 'gps_lat' => 0.0463, 'gps_lng' => 37.6559, 'phone' => '+254 722 110011', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Imenti Agrovet', 'county' => 'Meru', 'sub_county' => 'Imenti North', 'town' => 'Meru', 'gps_lat' => 0.0511, 'gps_lng' => 37.6420, 'phone' => '+254 720 220022', 'stocks' => $fertChem, 'is_pcpb_certified' => true],
            ['name' => 'Maua Agrovet', 'county' => 'Meru', 'sub_county' => 'Igembe South', 'town' => 'Maua', 'gps_lat' => 0.2329, 'gps_lng' => 37.9404, 'phone' => '+254 721 330033', 'stocks' => $all, 'is_pcpb_certified' => false],
            ['name' => 'Nkubu Farm Inputs', 'county' => 'Meru', 'sub_county' => 'Imenti South', 'town' => 'Nkubu', 'gps_lat' => -0.0625, 'gps_lng' => 37.6680, 'phone' => '+254 722 440044', 'stocks' => $seedFert, 'is_pcpb_certified' => false],

            // Kirinyaga — JAICA pilot county
            ['name' => 'Mwea Agrovet Centre', 'county' => 'Kirinyaga', 'sub_county' => 'Mwea', 'town' => 'Wanguru', 'gps_lat' => -0.6892, 'gps_lng' => 37.3725, 'phone' => '+254 722 550055', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Kerugoya Farm Supplies', 'county' => 'Kirinyaga', 'town' => 'Kerugoya', 'gps_lat' => -0.4980, 'gps_lng' => 37.2806, 'phone' => '+254 720 660066', 'stocks' => $fertChem, 'is_pcpb_certified' => true],
            ['name' => 'Sagana Agrovet', 'county' => 'Kirinyaga', 'town' => 'Sagana', 'gps_lat' => -0.6678, 'gps_lng' => 37.2070, 'phone' => '+254 722 770077', 'stocks' => $all, 'is_pcpb_certified' => false],

            // Nyeri
            ['name' => 'Nyeri Agro Centre', 'county' => 'Nyeri', 'town' => 'Nyeri', 'gps_lat' => -0.4172, 'gps_lng' => 36.9476, 'phone' => '+254 722 880088', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Karatina Farmers Inputs', 'county' => 'Nyeri', 'town' => 'Karatina', 'gps_lat' => -0.4844, 'gps_lng' => 37.1283, 'phone' => '+254 720 990099', 'stocks' => $fertChem, 'is_pcpb_certified' => false],

            // Embu
            ['name' => 'Embu Agrovet', 'county' => 'Embu', 'town' => 'Embu', 'gps_lat' => -0.5310, 'gps_lng' => 37.4500, 'phone' => '+254 722 100200', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Runyenjes Inputs', 'county' => 'Embu', 'town' => 'Runyenjes', 'gps_lat' => -0.4225, 'gps_lng' => 37.5663, 'phone' => '+254 720 200300', 'stocks' => $seedFert, 'is_pcpb_certified' => false],

            // Murang'a
            ['name' => 'Muranga Farm Centre', 'county' => "Murang'a", 'town' => "Murang'a", 'gps_lat' => -0.7210, 'gps_lng' => 37.1530, 'phone' => '+254 722 300400', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Kandara Agrovet', 'county' => "Murang'a", 'town' => 'Kandara', 'gps_lat' => -0.9222, 'gps_lng' => 36.9444, 'phone' => '+254 720 400500', 'stocks' => $fertChem, 'is_pcpb_certified' => false],

            // Machakos
            ['name' => 'Machakos Agrovet', 'county' => 'Machakos', 'town' => 'Machakos', 'gps_lat' => -1.5167, 'gps_lng' => 37.2667, 'phone' => '+254 722 500600', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Kangundo Farm Inputs', 'county' => 'Machakos', 'town' => 'Kangundo', 'gps_lat' => -1.3000, 'gps_lng' => 37.3500, 'phone' => '+254 720 600700', 'stocks' => $seedFert, 'is_pcpb_certified' => false],

            // Kiambu
            ['name' => 'Limuru Agrovet', 'county' => 'Kiambu', 'town' => 'Limuru', 'gps_lat' => -1.1135, 'gps_lng' => 36.6435, 'phone' => '+254 722 700800', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Thika Farm Centre', 'county' => 'Kiambu', 'town' => 'Thika', 'gps_lat' => -1.0333, 'gps_lng' => 37.0833, 'phone' => '+254 720 800900', 'stocks' => $fertChem, 'is_pcpb_certified' => true],

            // Nakuru
            ['name' => 'Nakuru Farm Supplies', 'county' => 'Nakuru', 'town' => 'Nakuru', 'gps_lat' => -0.3031, 'gps_lng' => 36.0800, 'phone' => '+254 722 900100', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Naivasha Agrovet', 'county' => 'Nakuru', 'town' => 'Naivasha', 'gps_lat' => -0.7172, 'gps_lng' => 36.4310, 'phone' => '+254 720 100200', 'stocks' => $all, 'is_pcpb_certified' => true],

            // Western
            ['name' => 'Bungoma Farm Centre', 'county' => 'Bungoma', 'town' => 'Bungoma', 'gps_lat' => 0.5635, 'gps_lng' => 34.5606, 'phone' => '+254 722 200300', 'stocks' => $all, 'is_pcpb_certified' => false],
            ['name' => 'Kakamega Agrovet', 'county' => 'Kakamega', 'town' => 'Kakamega', 'gps_lat' => 0.2827, 'gps_lng' => 34.7519, 'phone' => '+254 720 300400', 'stocks' => $fertChem, 'is_pcpb_certified' => true],

            // Kisii
            ['name' => 'Kisii Farm Supplies', 'county' => 'Kisii', 'town' => 'Kisii', 'gps_lat' => -0.6800, 'gps_lng' => 34.7700, 'phone' => '+254 722 400500', 'stocks' => $all, 'is_pcpb_certified' => false],

            // Trans Nzoia + Uasin Gishu (highland farming belt)
            ['name' => 'Kitale Agro Centre', 'county' => 'Trans Nzoia', 'town' => 'Kitale', 'gps_lat' => 1.0157, 'gps_lng' => 35.0064, 'phone' => '+254 722 500600', 'stocks' => $all, 'is_pcpb_certified' => true],
            ['name' => 'Eldoret Farm Inputs', 'county' => 'Uasin Gishu', 'town' => 'Eldoret', 'gps_lat' => 0.5143, 'gps_lng' => 35.2698, 'phone' => '+254 722 600700', 'stocks' => $all, 'is_pcpb_certified' => true],
        ];
    }
}
