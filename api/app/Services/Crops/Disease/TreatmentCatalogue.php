<?php

namespace App\Services\Crops\Disease;

/**
 * Local PCPB treatment catalogue — extended to all 20 enriched crops (S1, 2026-11).
 *
 * Kindwise's live identification API (model crop_health:1.2.1) rejects the
 * `details` modifier, so treatment data never arrives in the API response.
 * Treatments are therefore sourced locally: diagnosis name → curated
 * generic + PCPB-registered products. The mock provider draws from the
 * same catalogue so demo and live behaviour agree.
 *
 * Vocabulary bridge: every entry now carries the disease `key` from the
 * crop JSON files (api/resources/content/crops/*.json), so the catalogue and
 * the crop content reference each other by key AND display name.
 *
 * Advisory discipline: treatment entries are advisory. Product names are the
 * commonly stocked PCPB-registered brands; verify current PCPB registration
 * status before printing. 'None' entries are incurable diseases — the advice
 * is prevention and field hygiene.
 */
final class TreatmentCatalogue
{
    /**
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const GENERIC_DISEASES = [
            [
                'key' => "powdery_mildew",
                'name' => "Powdery Mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "aphid_infestation",
                'name' => "Aphid Infestation",
                'treatments' => [
                    [
                        'generic' => "Pyrethrin",
                        'pcpb' => "Ambush Super 200 EC",
                        'application_notes' => "Selective — preserves beneficial insects.",
                    ],
                ],
            ],    ];
    /**
     * Tomato-specific entries — names match the Kindwise API naming; keys
     * bridge to tomato.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const TOMATO_DISEASES = [
            [
                'key' => "early_blight",
                'name' => "Early Blight",
                'treatments' => [
                    [
                        'generic' => "Mancozeb 80WP",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Spray every 7-10 days at first sign.",
                    ],
                    [
                        'generic' => "Copper Hydroxide",
                        'pcpb' => "Funguran-OH 50 WP",
                        'application_notes' => "Preventive — alternate with Ridomil to avoid resistance.",
                    ],
                ],
            ],            [
                'key' => "late_blight",
                'name' => "Late Blight",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Apply within 24h of detection. Critical in cool wet weather.",
                    ],
                ],
            ],            [
                'key' => "bacterial_wilt",
                'name' => "Bacterial Wilt",
                'treatments' => [
                    [
                        'generic' => "None — rogue and destroy; 3-season rotation away from solanaceous crops. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rogue and destroy; 3-season rotation away from solanaceous crops.",
                    ],
                ],
            ],            [
                'key' => "tuta_absoluta",
                'name' => "Tuta Absoluta",
                'treatments' => [
                    [
                        'generic' => "Chlorantraniliprole",
                        'pcpb' => "Coragen 20 SC",
                        'application_notes' => "Apply at first mines; mass trapping complements sprays.",
                    ],
                ],
            ],            [
                'key' => "tomato_yellow_leaf_curl_virus",
                'name' => "Tomato Yellow Leaf Curl Virus (TYLCV)",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Confidor 200 SL",
                        'application_notes' => "Targets the whitefly vector — not the virus itself. Plant TYLCV-resistant varieties (Tylka F1).",
                    ],
                ],
            ],            [
                'key' => "fusarium_wilt",
                'name' => "Fusarium Wilt",
                'treatments' => [
                    [
                        'generic' => "None — remove plants; resistant varieties and rotation. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — remove plants; resistant varieties and rotation.",
                    ],
                ],
            ],    ];
    /**
     * Kale entries — keys bridge to kale.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const KALE_DISEASES = [
            [
                'key' => "diamondback_moth",
                'name' => "Diamondback moth",
                'treatments' => [
                    [
                        'generic' => "Lufenuron",
                        'pcpb' => "Match 050 EC",
                        'application_notes' => "Rotate chemical classes — DBM builds resistance fast.",
                    ],
                ],
            ],            [
                'key' => "aphids",
                'name' => "Aphids",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Apply only if colonies spread; natural enemies control early outbreaks.",
                    ],
                ],
            ],            [
                'key' => "black_rot",
                'name' => "Black rot",
                'treatments' => [
                    [
                        'generic' => "Copper Hydroxide",
                        'pcpb' => "Funguran-OH 50 WP",
                        'application_notes' => "Suppresses spread only — no cure. Remove infected plants.",
                    ],
                ],
            ],            [
                'key' => "downy_mildew",
                'name' => "Downy mildew",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M + Mancozeb",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Preventive — apply at first specks in cool damp spells.",
                    ],
                ],
            ],            [
                'key' => "alternaria_leaf_spot",
                'name' => "Alternaria leaf spot",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first spots; repeat 7-10 days in wet spells.",
                    ],
                ],
            ],    ];
    /**
     * French Beans entries — keys bridge to french-beans.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const FRENCH_BEANS_DISEASES = [
            [
                'key' => "rust",
                'name' => "Rust",
                'treatments' => [
                    [
                        'generic' => "Tebuconazole",
                        'pcpb' => "Folicur 250 EW",
                        'application_notes' => "Apply at first pustules on leaf undersides.",
                    ],
                ],
            ],            [
                'key' => "anthracnose",
                'name' => "Anthracnose",
                'treatments' => [
                    [
                        'generic' => "Azoxystrobin",
                        'pcpb' => "Ortiva 250 SC",
                        'application_notes' => "Apply at flowering and fruit set in wet weather.",
                    ],
                ],
            ],            [
                'key' => "bean_fly",
                'name' => "Bean fly",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid 350 FS seed-dressing",
                        'pcpb' => null,
                        'application_notes' => "Seed-dress before sowing; foliar sprays rarely pay once tunnels form.",
                    ],
                ],
            ],            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "halo_blight",
                'name' => "Halo blight",
                'treatments' => [
                    [
                        'generic' => "Copper Hydroxide",
                        'pcpb' => "Funguran-OH 50 WP",
                        'application_notes' => "Suppression only — clean seed is the real control.",
                    ],
                ],
            ],            [
                'key' => "angular_leaf_spot",
                'name' => "Angular leaf spot",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first lesions; keep rows open.",
                    ],
                ],
            ],            [
                'key' => "root_rots",
                'name' => "Root rots",
                'treatments' => [
                    [
                        'generic' => "None — drainage and rotation are the cure. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No effective spray — drainage and rotation are the cure.",
                    ],
                ],
            ],    ];
    /**
     * Mango entries — keys bridge to mango.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const MANGO_DISEASES = [
            [
                'key' => "mango_fruit_fly",
                'name' => "Mango fruit fly",
                'treatments' => [
                    [
                        'generic' => "Spinosad protein bait",
                        'pcpb' => "GF-120 NF",
                        'application_notes' => "Bait sprays + orchard sanitation; remove fallen fruit.",
                    ],
                ],
            ],            [
                'key' => "anthracnose",
                'name' => "Anthracnose",
                'treatments' => [
                    [
                        'generic' => "Azoxystrobin",
                        'pcpb' => "Ortiva 250 SC",
                        'application_notes' => "Apply at flowering and fruit set in wet weather.",
                    ],
                ],
            ],            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "thrips",
                'name' => "Thrips",
                'treatments' => [
                    [
                        'generic' => "Lambda-cyhalothrin",
                        'pcpb' => "Karate Zeon 2.5 CS",
                        'application_notes' => "Apply at threshold; monitor during dry spells.",
                    ],
                ],
            ],            [
                'key' => "mealybug",
                'name' => "Mealybugs",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Apply at threshold; oil sprays for heavy colonies.",
                    ],
                ],
            ],            [
                'key' => "sooty_mould",
                'name' => "Sooty mould",
                'treatments' => [
                    [
                        'generic' => "None — the mould follows the honeydew. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "Control the sap-sucking insects — the mould follows the honeydew.",
                    ],
                ],
            ],    ];
    /**
     * Bulb Onion entries — keys bridge to bulb-onion.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const ONION_DISEASES = [
            [
                'key' => "thrips",
                'name' => "Thrips",
                'treatments' => [
                    [
                        'generic' => "Lambda-cyhalothrin",
                        'pcpb' => "Karate Zeon 2.5 CS",
                        'application_notes' => "Apply at threshold; monitor during dry spells.",
                    ],
                ],
            ],            [
                'key' => "downy_mildew",
                'name' => "Downy mildew",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M + Mancozeb",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Preventive — apply at first specks in cool damp spells.",
                    ],
                ],
            ],            [
                'key' => "purple_blotch",
                'name' => "Purple blotch",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first purple-centred lesions.",
                    ],
                ],
            ],            [
                'key' => "neck_rot",
                'name' => "Neck rot (Botrytis)",
                'treatments' => [
                    [
                        'generic' => "None — cure necks 2-3 weeks and store dry. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No field cure — cure necks 2-3 weeks and store dry.",
                    ],
                ],
            ],            [
                'key' => "white_rot",
                'name' => "White rot",
                'treatments' => [
                    [
                        'generic' => "None — rotation and clean tools only. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rotation and clean tools only.",
                    ],
                ],
            ],    ];
    /**
     * Avocado entries — keys bridge to avocado.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const AVOCADO_DISEASES = [
            [
                'key' => "false_codling_moth",
                'name' => "False codling moth",
                'treatments' => [
                    [
                        'generic' => "Lambda-cyhalothrin",
                        'pcpb' => "Karate Zeon 2.5 CS",
                        'application_notes' => "Orchard schedule + sanitation; traps for monitoring.",
                    ],
                ],
            ],            [
                'key' => "anthracnose",
                'name' => "Anthracnose",
                'treatments' => [
                    [
                        'generic' => "Azoxystrobin",
                        'pcpb' => "Ortiva 250 SC",
                        'application_notes' => "Apply at flowering and fruit set in wet weather.",
                    ],
                ],
            ],            [
                'key' => "phytophthora_root_rot",
                'name' => "Root rot (Phytophthora)",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Collar drench at first symptoms — prevention is drainage and clean seedlings.",
                    ],
                ],
            ],            [
                'key' => "thrips_scales",
                'name' => "Thrips and scales",
                'treatments' => [
                    [
                        'generic' => "Lambda-cyhalothrin",
                        'pcpb' => "Karate Zeon 2.5 CS",
                        'application_notes' => "Apply at threshold; preserve natural enemies.",
                    ],
                ],
            ],            [
                'key' => "cercospora_leaf_spot",
                'name' => "Cercospora leaf spot",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first spots; prune for airflow.",
                    ],
                ],
            ],    ];
    /**
     * Banana entries — keys bridge to banana.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const BANANA_DISEASES = [
            [
                'key' => "panama_disease",
                'name' => "Panama disease (Fusarium wilt)",
                'treatments' => [
                    [
                        'generic' => "None — biosecurity: clean plantlets, clean tools, never move soil. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — biosecurity: clean plantlets, clean tools, never move soil.",
                    ],
                ],
            ],            [
                'key' => "black_sigatoka",
                'name' => "Black sigatoka",
                'treatments' => [
                    [
                        'generic' => "Difenoconazole",
                        'pcpb' => "Score 250 EC",
                        'application_notes' => "Fungicide programme + de-leafing infected leaves.",
                    ],
                ],
            ],            [
                'key' => "banana_weevil",
                'name' => "Banana weevil",
                'treatments' => [
                    [
                        'generic' => "Trapping + registered insecticide",
                        'pcpb' => null,
                        'application_notes' => "Apply at threshold; clean TC plantlets and remove old stumps.",
                    ],
                ],
            ],            [
                'key' => "nematodes",
                'name' => "Nematodes",
                'treatments' => [
                    [
                        'generic' => "None — clean TC plantlets are the control. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No field cure — clean TC plantlets are the control.",
                    ],
                ],
            ],            [
                'key' => "bunchy_top_virus",
                'name' => "Bunchy top virus (BBTV)",
                'treatments' => [
                    [
                        'generic' => "None — rogue and destroy immediately; control aphids. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rogue and destroy immediately; control aphids.",
                    ],
                ],
            ],            [
                'key' => "cigar_end_rot",
                'name' => "Cigar end rot",
                'treatments' => [
                    [
                        'generic' => "None — Remove the male bud after fruit set; improve airflow. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "Remove the male bud after fruit set; improve airflow.",
                    ],
                ],
            ],    ];
    /**
     * Capsicum entries — keys bridge to capsicum.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const CAPSICUM_DISEASES = [
            [
                'key' => "whiteflies_viruses",
                'name' => "Whiteflies + viruses",
                'treatments' => [
                    [
                        'generic' => "Thiamethoxam",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Targets the vector — the virus itself has no cure. Rogue infected plants.",
                    ],
                ],
            ],            [
                'key' => "aphids",
                'name' => "Aphids",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Apply only if colonies spread; natural enemies control early outbreaks.",
                    ],
                ],
            ],            [
                'key' => "thrips",
                'name' => "Thrips",
                'treatments' => [
                    [
                        'generic' => "Lambda-cyhalothrin",
                        'pcpb' => "Karate Zeon 2.5 CS",
                        'application_notes' => "Apply at threshold; monitor during dry spells.",
                    ],
                ],
            ],            [
                'key' => "bacterial_wilt",
                'name' => "Bacterial wilt",
                'treatments' => [
                    [
                        'generic' => "None — rogue and destroy; 3-season rotation away from solanaceous crops. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rogue and destroy; 3-season rotation away from solanaceous crops.",
                    ],
                ],
            ],            [
                'key' => "phytophthora_blight",
                'name' => "Phytophthora blight",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M + Mancozeb",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Preventive in wet spells; drainage and raised beds.",
                    ],
                ],
            ],            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "anthracnose",
                'name' => "Anthracnose fruit rot",
                'treatments' => [
                    [
                        'generic' => "Azoxystrobin",
                        'pcpb' => "Ortiva 250 SC",
                        'application_notes' => "Apply at flowering and fruit set in wet weather.",
                    ],
                ],
            ],    ];
    /**
     * Potato entries — keys bridge to potato.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const POTATO_DISEASES = [
            [
                'key' => "late_blight",
                'name' => "Late blight",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Apply within 24h of detection. Critical in cool wet weather.",
                    ],
                ],
            ],            [
                'key' => "early_blight",
                'name' => "Early blight",
                'treatments' => [
                    [
                        'generic' => "Mancozeb 80WP",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Spray every 7-10 days at first sign.",
                    ],
                    [
                        'generic' => "Copper Hydroxide",
                        'pcpb' => "Funguran-OH 50 WP",
                        'application_notes' => "Preventive — alternate with Ridomil to avoid resistance.",
                    ],
                ],
            ],            [
                'key' => "bacterial_wilt",
                'name' => "Bacterial wilt",
                'treatments' => [
                    [
                        'generic' => "None — rogue and destroy; 3-season rotation away from solanaceous crops. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rogue and destroy; 3-season rotation away from solanaceous crops.",
                    ],
                ],
            ],            [
                'key' => "viruses",
                'name' => "Potato viruses (PLRV/PVY)",
                'treatments' => [
                    [
                        'generic' => "None — certified seed and early aphid control are the defence. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — certified seed and early aphid control are the defence.",
                    ],
                ],
            ],            [
                'key' => "potato_tuber_moth",
                'name' => "Potato tuber moth",
                'treatments' => [
                    [
                        'generic' => "Lambda-cyhalothrin",
                        'pcpb' => "Karate Zeon 2.5 CS",
                        'application_notes' => "Earth up thoroughly; harvest on time.",
                    ],
                ],
            ],            [
                'key' => "leafminer",
                'name' => "Leaf miners",
                'treatments' => [
                    [
                        'generic' => "Cyromazine",
                        'pcpb' => "Trigard 75 WP",
                        'application_notes' => "Apply at threshold; remove mined leaves.",
                    ],
                ],
            ],    ];
    /**
     * Watermelon entries — keys bridge to watermelon.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const WATERMELON_DISEASES = [
            [
                'key' => "melon_fly",
                'name' => "Fruit fly (melon fly)",
                'treatments' => [
                    [
                        'generic' => "Spinosad protein bait",
                        'pcpb' => "GF-120 NF",
                        'application_notes' => "Bait sprays + remove fallen fruit; protect young fruit.",
                    ],
                ],
            ],            [
                'key' => "aphids",
                'name' => "Aphids",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Apply only if colonies spread; natural enemies control early outbreaks.",
                    ],
                ],
            ],            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "downy_mildew",
                'name' => "Downy mildew",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M + Mancozeb",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Preventive — apply at first specks in cool damp spells.",
                    ],
                ],
            ],            [
                'key' => "anthracnose",
                'name' => "Anthracnose",
                'treatments' => [
                    [
                        'generic' => "Azoxystrobin",
                        'pcpb' => "Ortiva 250 SC",
                        'application_notes' => "Apply at flowering and fruit set in wet weather.",
                    ],
                ],
            ],            [
                'key' => "gummy_stem_blight",
                'name' => "Gummy stem blight",
                'treatments' => [
                    [
                        'generic' => "Chlorothalonil",
                        'pcpb' => "Bravo 720 SC",
                        'application_notes' => "Apply at first cankers; clean rotation.",
                    ],
                ],
            ],            [
                'key' => "fusarium_wilt",
                'name' => "Fusarium wilt",
                'treatments' => [
                    [
                        'generic' => "None — remove plants; resistant varieties and rotation. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — remove plants; resistant varieties and rotation.",
                    ],
                ],
            ],    ];
    /**
     * Maize entries — keys bridge to maize.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const MAIZE_DISEASES = [
            [
                'key' => "fall_armyworm",
                'name' => "Fall armyworm",
                'treatments' => [
                    [
                        'generic' => "Chlorantraniliprole",
                        'pcpb' => "Coragen 20 SC",
                        'application_notes' => "Apply at first whorl damage — early instars only; hand-crush in small plots.",
                    ],
                ],
            ],            [
                'key' => "stalk_borer",
                'name' => "Maize stalk borer",
                'treatments' => [
                    [
                        'generic' => "Lambda-cyhalothrin",
                        'pcpb' => "Karate Zeon 2.5 CS",
                        'application_notes' => "Apply before tunnelling starts; destroy stalks after harvest.",
                    ],
                ],
            ],            [
                'key' => "grey_leaf_spot",
                'name' => "Grey leaf spot",
                'treatments' => [
                    [
                        'generic' => "Tebuconazole",
                        'pcpb' => "Folicur 250 EW",
                        'application_notes' => "Apply at early onset on lower leaves.",
                    ],
                ],
            ],            [
                'key' => "maize_lethal_necrosis",
                'name' => "Maize lethal necrosis (MLN)",
                'treatments' => [
                    [
                        'generic' => "None — rogue and destroy; report to KEPHIS. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rogue and destroy; report to KEPHIS.",
                    ],
                ],
            ],            [
                'key' => "streak_virus",
                'name' => "Maize streak virus",
                'treatments' => [
                    [
                        'generic' => "None — leafhopper vector control and tolerant varieties. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — leafhopper vector control and tolerant varieties.",
                    ],
                ],
            ],    ];
    /**
     * Beans entries — keys bridge to beans.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const BEANS_DISEASES = [
            [
                'key' => "bean_fly",
                'name' => "Bean fly",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid 350 FS seed-dressing",
                        'pcpb' => null,
                        'application_notes' => "Seed-dress before sowing; foliar sprays rarely pay once tunnels form.",
                    ],
                ],
            ],            [
                'key' => "angular_leaf_spot",
                'name' => "Angular leaf spot",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first lesions; keep rows open.",
                    ],
                ],
            ],            [
                'key' => "anthracnose",
                'name' => "Anthracnose",
                'treatments' => [
                    [
                        'generic' => "Azoxystrobin",
                        'pcpb' => "Ortiva 250 SC",
                        'application_notes' => "Apply at flowering and fruit set in wet weather.",
                    ],
                ],
            ],            [
                'key' => "rust",
                'name' => "Bean rust",
                'treatments' => [
                    [
                        'generic' => "Tebuconazole",
                        'pcpb' => "Folicur 250 EW",
                        'application_notes' => "Apply at first pustules on leaf undersides.",
                    ],
                ],
            ],            [
                'key' => "root_rots",
                'name' => "Root rots",
                'treatments' => [
                    [
                        'generic' => "None — drainage and rotation are the cure. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No effective spray — drainage and rotation are the cure.",
                    ],
                ],
            ],    ];
    /**
     * Sweet Potato entries — keys bridge to sweet-potato.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const SWEET_POTATO_DISEASES = [
            [
                'key' => "sweet_potato_weevil",
                'name' => "Sweet potato weevil",
                'treatments' => [
                    [
                        'generic' => "None — harvest on time, cover cracks. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No effective post-infestation treatment — harvest on time, cover cracks.",
                    ],
                ],
            ],            [
                'key' => "spvd",
                'name' => "Sweet potato virus disease (SPVD)",
                'treatments' => [
                    [
                        'generic' => "None — plant clean vines only; control whiteflies. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — plant clean vines only; control whiteflies.",
                    ],
                ],
            ],            [
                'key' => "root_knot_nematodes",
                'name' => "Root-knot nematodes",
                'treatments' => [
                    [
                        'generic' => "None — rotation with cereals is the treatment. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rotation with cereals is the treatment.",
                    ],
                ],
            ],            [
                'key' => "alternaria_leaf_spot",
                'name' => "Alternaria leaf spot",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first spots; repeat 7-10 days in wet spells.",
                    ],
                ],
            ],    ];
    /**
     * Spinach entries — keys bridge to spinach.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const SPINACH_DISEASES = [
            [
                'key' => "aphids",
                'name' => "Aphids",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Apply only if colonies spread; natural enemies control early outbreaks.",
                    ],
                ],
            ],            [
                'key' => "leaf_miners",
                'name' => "Leaf miners",
                'treatments' => [
                    [
                        'generic' => "Cyromazine",
                        'pcpb' => "Trigard 75 WP",
                        'application_notes' => "Apply at threshold; remove mined leaves.",
                    ],
                ],
            ],            [
                'key' => "downy_mildew",
                'name' => "Downy mildew",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M + Mancozeb",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Preventive — apply at first specks in cool damp spells.",
                    ],
                ],
            ],            [
                'key' => "cercospora_leaf_spot",
                'name' => "Cercospora leaf spot",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first spots; prune for airflow.",
                    ],
                ],
            ],    ];
    /**
     * Carrot entries — keys bridge to carrot.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const CARROT_DISEASES = [
            [
                'key' => "alternaria_leaf_blight",
                'name' => "Alternaria leaf blight",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first ringed spots; rotate crops.",
                    ],
                ],
            ],            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "root_knot_nematodes",
                'name' => "Root-knot nematodes",
                'treatments' => [
                    [
                        'generic' => "None — rotation with cereals is the treatment. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rotation with cereals is the treatment.",
                    ],
                ],
            ],            [
                'key' => "cavity_spot",
                'name' => "Cavity spot",
                'treatments' => [
                    [
                        'generic' => "None — calcium balance + steady watering. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No direct treatment — calcium balance + steady watering.",
                    ],
                ],
            ],    ];
    /**
     * Butternut entries — keys bridge to butternut.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const BUTTERNUT_DISEASES = [
            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "downy_mildew",
                'name' => "Downy mildew",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M + Mancozeb",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Preventive — apply at first specks in cool damp spells.",
                    ],
                ],
            ],            [
                'key' => "melon_fly",
                'name' => "Fruit fly (melon fly)",
                'treatments' => [
                    [
                        'generic' => "Spinosad protein bait",
                        'pcpb' => "GF-120 NF",
                        'application_notes' => "Bait sprays + remove fallen fruit; protect young fruit.",
                    ],
                ],
            ],            [
                'key' => "viruses",
                'name' => "Viruses (WMV/ZYMV/PRSV)",
                'treatments' => [
                    [
                        'generic' => "None — certified seed and early aphid control are the defence. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — certified seed and early aphid control are the defence.",
                    ],
                ],
            ],            [
                'key' => "anthracnose",
                'name' => "Anthracnose",
                'treatments' => [
                    [
                        'generic' => "Azoxystrobin",
                        'pcpb' => "Ortiva 250 SC",
                        'application_notes' => "Apply at flowering and fruit set in wet weather.",
                    ],
                ],
            ],    ];
    /**
     * Coriander entries — keys bridge to coriander.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const CORIANDER_DISEASES = [
            [
                'key' => "damping_off",
                'name' => "Damping off",
                'treatments' => [
                    [
                        'generic' => "Thiram-class seed dressing",
                        'pcpb' => null,
                        'application_notes' => "Seed-dress where the nursery is wet; raised beds and thin sowing.",
                    ],
                ],
            ],            [
                'key' => "aphids",
                'name' => "Aphids",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Apply only if colonies spread; natural enemies control early outbreaks.",
                    ],
                ],
            ],            [
                'key' => "alternaria_leaf_spot",
                'name' => "Alternaria leaf spot",
                'treatments' => [
                    [
                        'generic' => "Mancozeb",
                        'pcpb' => "Dithane M-45 80WP",
                        'application_notes' => "Apply at first spots; repeat 7-10 days in wet spells.",
                    ],
                ],
            ],            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],    ];
    /**
     * Pawpaw entries — keys bridge to pawpaw.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const PAWPAW_DISEASES = [
            [
                'key' => "prsv",
                'name' => "Papaya ringspot virus (PRSV)",
                'treatments' => [
                    [
                        'generic' => "None — rogue immediately; tolerant varieties (Calina); control aphids. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — rogue immediately; tolerant varieties (Calina); control aphids.",
                    ],
                ],
            ],            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "anthracnose",
                'name' => "Anthracnose",
                'treatments' => [
                    [
                        'generic' => "Azoxystrobin",
                        'pcpb' => "Ortiva 250 SC",
                        'application_notes' => "Apply at flowering and fruit set in wet weather.",
                    ],
                ],
            ],            [
                'key' => "damping_off_root_rot",
                'name' => "Damping off / root rot",
                'treatments' => [
                    [
                        'generic' => "None — drainage and raised beds. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure once advanced — drainage and raised beds.",
                    ],
                ],
            ],            [
                'key' => "fruit_fly",
                'name' => "Fruit fly",
                'treatments' => [
                    [
                        'generic' => "Spinosad protein bait",
                        'pcpb' => "GF-120 NF",
                        'application_notes' => "Bait sprays + sanitation; remove fallen fruit.",
                    ],
                ],
            ],    ];
    /**
     * Snow Peas entries — keys bridge to snow-peas.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const SNOW_PEAS_DISEASES = [
            [
                'key' => "powdery_mildew",
                'name' => "Powdery mildew",
                'treatments' => [
                    [
                        'generic' => "Sulphur",
                        'pcpb' => "Thiovit Jet 80 WG",
                        'application_notes' => "Apply in cool morning hours. Avoid temps above 30°C.",
                    ],
                ],
            ],            [
                'key' => "downy_mildew",
                'name' => "Downy mildew",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M + Mancozeb",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Preventive — apply at first specks in cool damp spells.",
                    ],
                ],
            ],            [
                'key' => "aphids_viruses",
                'name' => "Aphids + viruses",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Targets the vector — the virus has no cure. Scout weekly.",
                    ],
                ],
            ],            [
                'key' => "root_rots",
                'name' => "Root rots",
                'treatments' => [
                    [
                        'generic' => "None — drainage and rotation are the cure. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No effective spray — drainage and rotation are the cure.",
                    ],
                ],
            ],            [
                'key' => "leaf_miners",
                'name' => "Leaf miners",
                'treatments' => [
                    [
                        'generic' => "Cyromazine",
                        'pcpb' => "Trigard 75 WP",
                        'application_notes' => "Apply at threshold; remove mined leaves.",
                    ],
                ],
            ],    ];
    /**
     * Cabbage entries — keys bridge to cabbage.json.
     *
     * @var list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    private const CABBAGE_DISEASES = [
            [
                'key' => "diamondback_moth",
                'name' => "Diamondback moth",
                'treatments' => [
                    [
                        'generic' => "Lufenuron",
                        'pcpb' => "Match 050 EC",
                        'application_notes' => "Rotate chemical classes — DBM builds resistance fast.",
                    ],
                ],
            ],            [
                'key' => "aphids",
                'name' => "Aphids",
                'treatments' => [
                    [
                        'generic' => "Imidacloprid",
                        'pcpb' => "Actara 25 WG",
                        'application_notes' => "Apply only if colonies spread; natural enemies control early outbreaks.",
                    ],
                ],
            ],            [
                'key' => "black_rot",
                'name' => "Black rot",
                'treatments' => [
                    [
                        'generic' => "Copper Hydroxide",
                        'pcpb' => "Funguran-OH 50 WP",
                        'application_notes' => "Suppresses spread only — no cure. Remove infected plants.",
                    ],
                ],
            ],            [
                'key' => "downy_mildew",
                'name' => "Downy mildew",
                'treatments' => [
                    [
                        'generic' => "Metalaxyl-M + Mancozeb",
                        'pcpb' => "Ridomil Gold MZ 68 WG",
                        'application_notes' => "Preventive — apply at first specks in cool damp spells.",
                    ],
                ],
            ],            [
                'key' => "head_rot_soft_rot",
                'name' => "Head rot (soft rot)",
                'treatments' => [
                    [
                        'generic' => "None — manage the insect damage that opens the door. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — manage the insect damage that opens the door.",
                    ],
                ],
            ],            [
                'key' => "fusarium_yellows",
                'name' => "Fusarium yellows",
                'treatments' => [
                    [
                        'generic' => "None — remove plants; resistant varieties where available. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — remove plants; resistant varieties where available.",
                    ],
                ],
            ],            [
                'key' => "club_root",
                'name' => "Club root",
                'treatments' => [
                    [
                        'generic' => "None — lime to raise pH above 7; long rotation away from brassicas. Prevention and field hygiene are the control.",
                        'pcpb' => null,
                        'application_notes' => "No cure — lime to raise pH above 7; long rotation away from brassicas.",
                    ],
                ],
            ],    ];
    /**
     * Full catalogue for a crop: crop-specific entries followed by the
     * generic entries (so unknown crops still get the two universal ones).
     *
     * @return list<array{key: string, name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    public static function catalogue(?string $cropSlug = null): array
    {
        $crop = match ($cropSlug) {
            'kale' => self::KALE_DISEASES,            'tomato' => self::TOMATO_DISEASES,            'french-beans' => self::FRENCH_BEANS_DISEASES,            'mango' => self::MANGO_DISEASES,            'bulb-onion' => self::ONION_DISEASES,            'avocado' => self::AVOCADO_DISEASES,            'banana' => self::BANANA_DISEASES,            'capsicum' => self::CAPSICUM_DISEASES,            'potato' => self::POTATO_DISEASES,            'watermelon' => self::WATERMELON_DISEASES,            'maize' => self::MAIZE_DISEASES,            'beans' => self::BEANS_DISEASES,            'sweet-potato' => self::SWEET_POTATO_DISEASES,            'spinach' => self::SPINACH_DISEASES,            'carrot' => self::CARROT_DISEASES,            'butternut' => self::BUTTERNUT_DISEASES,            'coriander' => self::CORIANDER_DISEASES,            'pawpaw' => self::PAWPAW_DISEASES,            'snow-peas' => self::SNOW_PEAS_DISEASES,            'cabbage' => self::CABBAGE_DISEASES,            default => [],
        };

        return array_merge($crop, self::GENERIC_DISEASES);
    }

    /**
     * Treatments for a diagnosis name (case-insensitive), or an empty list
     * when the diagnosis is not in the catalogue.
     *
     * @return list<array{generic: string, pcpb: ?string, application_notes?: string}>
     */
    public static function suggest(string $diagnosis, ?string $cropSlug = null): array
    {
        $needle = mb_strtolower(trim($diagnosis));

        foreach (self::catalogue($cropSlug) as $entry) {
            if (mb_strtolower($entry['name']) === $needle) {
                return $entry['treatments'];
            }
        }

        return [];
    }
}
