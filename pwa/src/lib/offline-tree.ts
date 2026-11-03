export interface OfflineTreeOption {
  label_en: string;
  label_sw: string;
  next?: string;
  diagnosis?: string;
}

export interface OfflineTreeQuestion {
  id: string;
  text_en: string;
  text_sw: string;
  options: OfflineTreeOption[];
}

export interface OfflineTreeCrop {
  slug: string;
  name_en: string;
  name_sw: string;
  first: string;
  questions: OfflineTreeQuestion[];
}

/**
 * Phase B3 pilot — hand-authored symptom trees for the 4-crop smoke set.
 * Questions are authored from the agronomist crop JSONs (symptoms_en +
 * scanner_notes discriminators). `diagnosis` values are disease KEYS from
 * the content layer; 'uncertain' means the tree can't tell offline.
 *
 * Honesty rule: these are guides, never high confidence. The offline
 * result always carries the 0.5 ceiling + an explicit confirm-with-photo
 * note (design spec: docs/scanner-offline-decision-tree.md).
 *
 * Swahili strings are machine-assisted drafts — native review pending
 * (same flag as the Phase A UI strings).
 */
export const OFFLINE_TREE: OfflineTreeCrop[] = [
  {
    slug: 'tomato',
    name_en: 'Tomato',
    name_sw: 'Nyanya',
    first: 'tomato_where',
    questions: [
      {
        id: 'tomato_where',
        text_en: 'Where is the problem on the plant?',
        text_sw: 'Tatizo liko wapi kwenye mmea?',
        options: [
          { label_en: 'Older leaves with brown rings', label_sw: 'Majani ya zamani yenye pete za kahawia', diagnosis: 'early_blight' },
          { label_en: 'White fuzz on leaves, spreading fast', label_sw: 'Manyoya meupe kwenye majani, yanaenea haraka', diagnosis: 'late_blight' },
          { label_en: 'Leaves curling up, turning yellow', label_sw: 'Majani yanajikunja juu na kugeuka njano', next: 'tomato_curl' },
          { label_en: 'Tunnels in leaves, pin-pricks on fruit', label_sw: 'Mashimo kwenye majani na michomo kwenye matunda', diagnosis: 'tuta_absoluta' },
          { label_en: 'The whole plant is wilting', label_sw: 'Mmea wote unanyauka', next: 'tomato_wilt' },
        ],
      },
      {
        id: 'tomato_curl',
        text_en: 'Do you see whiteflies under the leaves?',
        text_sw: 'Je, unaona wadudu weupe chini ya majani?',
        options: [
          { label_en: 'Yes, many whiteflies', label_sw: 'Ndiyo, wengi', diagnosis: 'tomato_yellow_leaf_curl_virus' },
          { label_en: 'No whiteflies', label_sw: 'Hapana', diagnosis: 'uncertain' },
        ],
      },
      {
        id: 'tomato_wilt',
        text_en: 'Cut a stem near the base — what do you see?',
        text_sw: 'Kata shina karibu na ardhi — unaona nini?',
        options: [
          { label_en: 'Sticky ooze coming out', label_sw: 'Utomvu unaonata unatoka', diagnosis: 'bacterial_wilt' },
          { label_en: 'A brown ring inside the stem', label_sw: 'Pete ya kahawia ndani ya shina', diagnosis: 'fusarium_wilt' },
          { label_en: 'Not sure', label_sw: 'Sina uhakika', diagnosis: 'uncertain' },
        ],
      },
    ],
  },
  {
    slug: 'kale',
    name_en: 'Kale',
    name_sw: 'Sukuma wiki',
    first: 'kale_what',
    questions: [
      {
        id: 'kale_what',
        text_en: 'What do you see on the leaves?',
        text_sw: 'Unaona nini kwenye majani?',
        options: [
          { label_en: 'Small green caterpillars', label_sw: 'Viwavi wadogo wa kijani', diagnosis: 'diamondback_moth' },
          { label_en: 'Tiny insects clustered under leaves', label_sw: 'Wadudu wadogo chini ya majani', diagnosis: 'aphids' },
          { label_en: 'Yellow patches with white fuzz underneath', label_sw: 'Madoa ya njano na manyoya meupe chini', diagnosis: 'downy_mildew' },
          { label_en: 'V-shaped yellow edges', label_sw: 'Kingo za njano zenye umbo la V', diagnosis: 'black_rot' },
          { label_en: 'Brown spots with rings', label_sw: 'Madoa ya kahawia yenye pete', diagnosis: 'alternaria_leaf_spot' },
        ],
      },
    ],
  },
  {
    slug: 'maize',
    name_en: 'Maize',
    name_sw: 'Mahindi',
    first: 'maize_where',
    questions: [
      {
        id: 'maize_where',
        text_en: 'Where is the damage?',
        text_sw: 'Uharibifu uko wapi?',
        options: [
          { label_en: 'A caterpillar inside the leaf whorl', label_sw: 'Kiwavi ndani ya kitovu cha majani', diagnosis: 'fall_armyworm' },
          { label_en: 'Holes near the stem base', label_sw: 'Mashimo karibu na msingi wa shina', diagnosis: 'stalk_borer' },
          { label_en: 'Long grey rectangles on leaves', label_sw: 'Mistari mirefu ya kijivu kwenye majani', diagnosis: 'grey_leaf_spot' },
          { label_en: 'Yellow streaks following the veins', label_sw: 'Michirizi ya njano inayofuata mishipa', diagnosis: 'streak_virus' },
          { label_en: 'Plants dying, leaves drying from the tip', label_sw: 'Mimea inakufa, majani yakikauka kutoka ncha', diagnosis: 'maize_lethal_necrosis' },
        ],
      },
    ],
  },
  {
    slug: 'pawpaw',
    name_en: 'Pawpaw',
    name_sw: 'Papai',
    first: 'pawpaw_what',
    questions: [
      {
        id: 'pawpaw_what',
        text_en: 'What do you see?',
        text_sw: 'Unaona nini?',
        options: [
          { label_en: 'Ring spots on the fruit', label_sw: 'Pete kwenye tunda', diagnosis: 'prsv' },
          { label_en: 'White powder on both sides of leaves', label_sw: 'Unga mweupe pande zote za majani', diagnosis: 'powdery_mildew' },
          { label_en: 'Dark sunken spots on fruit', label_sw: 'Madoa meusi yaliyozama kwenye tunda', diagnosis: 'anthracnose' },
          { label_en: 'Seedlings rotting / soft plant base', label_sw: 'Miche inaoza / msingi laini wa mmea', diagnosis: 'damping_off_root_rot' },
          { label_en: 'Maggots inside the fruit', label_sw: 'Funza ndani ya tunda', diagnosis: 'fruit_fly' },
        ],
      },
    ],
  },
];

export function findTreeCrop(slug: string): OfflineTreeCrop | undefined {
  return OFFLINE_TREE.find((c) => c.slug === slug);
}

export function walkTree(
  crop: OfflineTreeCrop,
  option: OfflineTreeOption,
): { nextQuestion?: OfflineTreeQuestion; diagnosis?: string } {
  if (option.next) {
    const next = crop.questions.find((q) => q.id === option.next);
    return { nextQuestion: next };
  }
  return { diagnosis: option.diagnosis };
}
