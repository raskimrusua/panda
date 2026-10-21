#!/usr/bin/env python3
"""
Panda Media Generator — brand assets + blog headers, programmatic PIL.

Pattern replicated from Shira's social_media/ toolkit (deterministic PIL
generation, brand palette constants, DM Sans, composable glyphs) adapted
for Panda's horticulture scenario. No AI cost, regenerable, versionable.

Usage:
    python3 social_media/generate_media.py --all
    python3 social_media/generate_media.py --og
    python3 social_media/generate_media.py --headers
    python3 social_media/generate_media.py --logo
    python3 social_media/generate_media.py --favicon

Outputs (into marketing/public/):
    og-image.png           1200x630  site-wide OG card
    logo-horizontal.png    800x200   transparent wordmark + leaf mark
    favicon.svg            leaf mark
    blog/<slug>.png        1600x900  per-crop hero headers
"""

import argparse
import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

# ─── Paths ───────────────────────────────────────────────────────────────────
BASE_DIR = Path(__file__).parent
FONTS_DIR = BASE_DIR / "assets" / "fonts"
PUBLIC_DIR = BASE_DIR.parent / "marketing" / "public"

FONT_REGULAR = str(FONTS_DIR / "DMSans-Regular.ttf")
FONT_ITALIC = str(FONTS_DIR / "DMSans-Italic.ttf")

# ─── Brand palette (single source of truth — marketing/src/styles/global.css) ─
HIGHLAND_GREEN = (27, 67, 50)
DEEP_FOREST = (13, 40, 24)
SOFT_GREEN = (82, 183, 136)
SAVANNA_SUN = (224, 122, 47)
TERRACOTTA = (196, 101, 46)
HARVEST_GOLD = (212, 147, 13)
BONE_WHITE = (255, 248, 240)
RICH_EARTH = (45, 45, 45)
WARM_CREAM = (245, 230, 211)
SKY_WASH = (232, 240, 232)
PANDA_LEAF = (116, 198, 157)
PANDA_DAWN = (255, 241, 224)

OG_SIZE = (1200, 630)
HEADER_SIZE = (1600, 900)

# ─── Font helpers ─────────────────────────────────────────────────────────────

def font(size, italic=False):
    return ImageFont.truetype(FONT_ITALIC if italic else FONT_REGULAR, size)


def hexa(color, alpha=255):
    return color + (alpha,)


# ─── Leaf mark (the "Panda." dot, grown into a mark) ─────────────────────────

def draw_leaf_mark(draw, cx, cy, size, fill=HIGHLAND_GREEN, accent=SAVANNA_SUN):
    """Two curling leaves forming a round bud + an orange sun dot."""
    s = size / 2
    # left leaf — a filled arc approximated by an ellipse rotated
    leaf = Image.new("RGBA", (int(size), int(size)), (0, 0, 0, 0))
    ld = ImageDraw.Draw(leaf)
    ld.ellipse([0, s * 0.55, size, size * 1.45], fill=hexa(fill))
    leaf = leaf.rotate(-35, resample=Image.BICUBIC)
    draw.bitmap((int(cx - size / 2), int(cy - size / 2)), leaf, None)
    # right leaf mirrored
    leaf2 = Image.new("RGBA", (int(size), int(size)), (0, 0, 0, 0))
    ld2 = ImageDraw.Draw(leaf2)
    ld2.ellipse([0, s * 0.55, size, size * 1.45], fill=hexa(SOFT_GREEN))
    leaf2 = leaf2.rotate(35, resample=Image.BICUBIC)
    draw.bitmap((int(cx - size / 2), int(cy - size / 2)), leaf2, None)
    # sun dot behind the join
    draw.ellipse([cx - s * 0.42, cy - s * 1.1, cx + s * 0.42, cy - s * 0.26], fill=hexa(accent))


def draw_leaf_dot(draw, cx, cy, r, fill=PANDA_LEAF):
    """The simple navbar dot: a round panda-leaf dot (for wordmark 'Panda.')."""
    draw.ellipse([cx - r, cy - r, cx + r, cy + r], fill=hexa(fill))


def wordmark(draw, x, y, size=64, fill=RICH_EARTH, dot=PANDA_LEAF):
    """Render 'Panda.' — text + panda-leaf dot, left-aligned at (x, y)."""
    f = font(size)
    text = "Panda"
    w = draw.textlength(text, font=f)
    draw.text((x, y), text, font=f, fill=hexa(fill))
    draw.ellipse(
        [x + w + size * 0.18, y + size * 0.72, x + w + size * 0.18 + size * 0.16,
         y + size * 0.72 + size * 0.16],
        fill=hexa(dot),
    )
    return x + w + size * 0.34


# ─── Scenery helpers (shared canvas furniture) ───────────────────────────────

def sun_disc(draw, cx, cy, r, fill=SAVANNA_SUN, alpha=60):
    draw.ellipse([cx - r, cy - r, cx + r, cy + r], fill=hexa(fill, alpha))


def ground_band(draw, w, h, y_top, y_bottom, fill=SOFT_GREEN, alpha=40):
    draw.rectangle([0, y_top, w, y_bottom], fill=hexa(fill, alpha))


# ─── Crop glyphs (flat, geometric, consistent) ───────────────────────────────

def glyph_kale(draw, cx, cy, s):
    """Rosette of rounded leaves radiating from a centre."""
    for i in range(9):
        ang = math.radians(i * 40 + 18)
        x = cx + math.cos(ang) * s * 0.42
        y = cy + math.sin(ang) * s * 0.42
        leaf = Image.new("RGBA", (int(s), int(s)), (0, 0, 0, 0))
        ld = ImageDraw.Draw(leaf)
        ld.ellipse([s * 0.25, s * 0.10, s * 0.75, s * 1.05], fill=hexa(HIGHLAND_GREEN if i % 2 else DEEP_FOREST))
        leaf = leaf.rotate(-math.degrees(ang), resample=Image.BICUBIC)
        draw.bitmap((int(x - s / 2), int(y - s / 2)), leaf, None)
    draw.ellipse([cx - s * 0.10, cy - s * 0.10, cx + s * 0.10, cy + s * 0.10], fill=hexa(SOFT_GREEN))


def glyph_tomato(draw, cx, cy, s):
    """Cluster of three fruit + calyx star + stem."""
    r = s * 0.26
    for dx, dy in [(-0.55, 0.25), (0.55, 0.25), (0, -0.20)]:
        draw.ellipse([cx + dx * s - r, cy + dy * s - r, cx + dx * s + r, cy + dy * s + r],
                     fill=hexa(TERRACOTTA))
        draw.ellipse([cx + dx * s - r * 0.25, cy + dy * s - r * 0.45, cx + dx * s + r * 0.25,
                      cy + dy * s - r * 0.05], fill=hexa(SAVANNA_SUN, 120))
    # calyx + stem
    for i in range(5):
        ang = math.radians(i * 72 - 90)
        x1 = cx + math.cos(ang) * s * 0.12
        y1 = cy - s * 0.42 + math.sin(ang) * s * 0.12
        draw.line([cx, cy - s * 0.42, x1, y1], fill=hexa(HIGHLAND_GREEN), width=max(3, int(s * 0.045)))
    draw.line([cx, cy - s * 0.42, cx + s * 0.1, cy - s * 0.75], fill=hexa(HIGHLAND_GREEN),
              width=max(3, int(s * 0.05)))


def glyph_beans(draw, cx, cy, s):
    """Three pods on a vine arc."""
    for i, ang in enumerate([-35, 0, 35]):
        pod = Image.new("RGBA", (int(s), int(s)), (0, 0, 0, 0))
        pd = ImageDraw.Draw(pod)
        pd.rounded_rectangle([s * 0.25, s * 0.38, s * 0.75, s * 0.95], radius=s * 0.16,
                             fill=hexa(PANDA_LEAF if i == 1 else HIGHLAND_GREEN))
        pd.line([s * 0.5, s * 0.42, s * 0.5, s * 0.90], fill=hexa(DEEP_FOREST), width=max(2, int(s * 0.02)))
        pod = pod.rotate(ang, resample=Image.BICUBIC)
        draw.bitmap((int(cx - s / 2), int(cy - s / 2)), pod, None)
    draw.arc([cx - s * 0.75, cy - s * 0.9, cx + s * 0.75, cy + s * 0.9], start=200, end=340,
             fill=hexa(HIGHLAND_GREEN), width=max(4, int(s * 0.045)))


def glyph_mango(draw, cx, cy, s):
    """Oval fruit with a leaf pair on top."""
    fruit = Image.new("RGBA", (int(s), int(s)), (0, 0, 0, 0))
    fd = ImageDraw.Draw(fruit)
    fd.ellipse([s * 0.30, s * 0.42, s * 0.72, s * 1.02], fill=hexa(HARVEST_GOLD))
    fd.ellipse([s * 0.30, s * 0.42, s * 0.72, s * 0.78], fill=hexa(SAVANNA_SUN, 140))
    fruit = fruit.rotate(12, resample=Image.BICUBIC)
    draw.bitmap((int(cx - s / 2), int(cy - s / 2)), fruit, None)
    # leaf pair
    for ang in (-28, 28):
        leaf = Image.new("RGBA", (int(s * 0.6), int(s * 0.6)), (0, 0, 0, 0))
        ld = ImageDraw.Draw(leaf)
        ld.ellipse([s * 0.06, s * 0.10, s * 0.54, s * 0.56], fill=hexa(HIGHLAND_GREEN))
        leaf = leaf.rotate(ang, resample=Image.BICUBIC)
        draw.bitmap((int(cx - s * 0.3), int(cy - s * 0.62)), leaf, None)


def glyph_onion(draw, cx, cy, s):
    """Bulb with layered bands + shoots."""
    # shoots
    for dx, h in [(-0.16, 0.5), (0.0, 0.62), (0.16, 0.5)]:
        draw.line([cx, cy - s * 0.18, cx + dx * s, cy - s * h], fill=hexa(HIGHLAND_GREEN),
                  width=max(3, int(s * 0.04)))
    # bulb
    draw.polygon(
        [(cx - s * 0.38, cy - s * 0.12), (cx + s * 0.38, cy - s * 0.12),
         (cx + s * 0.42, cy + s * 0.28), (cx + s * 0.10, cy + s * 0.42),
         (cx - s * 0.10, cy + s * 0.42), (cx - s * 0.42, cy + s * 0.28)],
        fill=hexa(BONE_WHITE), outline=hexa(HARVEST_GOLD), width=max(3, int(s * 0.02)),
    )
    for frac in (0.62, 0.78):
        y = cy + s * (frac - 0.18)
        draw.arc([cx - s * 0.34, y - s * 0.10, cx + s * 0.34, y + s * 0.10], start=180, end=360,
                 fill=hexa(HARVEST_GOLD), width=max(2, int(s * 0.018)))



def glyph_drip(draw, cx, cy, s):
    """Drip line with emitters and a falling drop."""
    draw.line([cx - s * 0.55, cy - s * 0.25, cx + s * 0.55, cy - s * 0.25],
              fill=hexa(HIGHLAND_GREEN), width=max(5, int(s * 0.05)))
    for dx in (-0.35, 0.0, 0.35):
        x = cx + dx * s
        draw.line([x, cy - s * 0.25, x, cy + s * 0.05], fill=hexa(PANDA_LEAF),
                  width=max(3, int(s * 0.03)))
        drop = Image.new("RGBA", (int(s * 0.22), int(s * 0.22)), (0, 0, 0, 0))
        dd = ImageDraw.Draw(drop)
        dd.ellipse([s * 0.02, s * 0.02, s * 0.20, s * 0.20], fill=hexa(SAVANNA_SUN))
        drop = drop.rotate(45, resample=Image.BICUBIC)
        draw.bitmap((int(x - s * 0.11), int(cy + s * 0.18)), drop, None)


def glyph_scan(draw, cx, cy, s):
    """Sick leaf inside a camera frame."""
    leaf = Image.new("RGBA", (int(s), int(s)), (0, 0, 0, 0))
    ld = ImageDraw.Draw(leaf)
    ld.ellipse([s * 0.20, s * 0.10, s * 0.80, s * 1.05], fill=hexa(HIGHLAND_GREEN))
    leaf = leaf.rotate(-20, resample=Image.BICUBIC)
    draw.bitmap((int(cx - s / 2), int(cy - s / 2)), leaf, None)
    # lesion spots
    for dx, dy in [(-0.05, 0.1), (0.22, -0.05), (0.05, -0.35)]:
        draw.ellipse([cx + dx * s - s * 0.07, cy + dy * s - s * 0.07,
                      cx + dx * s + s * 0.07, cy + dy * s + s * 0.07],
                     fill=hexa(TERRACOTTA))
    # camera frame corners
    l = s * 0.05
    r = s * 0.62
    for cx0, cy0 in [(cx - r, cy - r), (cx + r, cy - r), (cx - r, cy + r), (cx + r, cy + r)]:
        sx = 1 if cx0 < cx else -1
        sy = 1 if cy0 < cy else -1
        draw.line([cx0, cy0, cx0 + sx * l, cy0], fill=hexa(SAVANNA_SUN), width=max(5, int(s * 0.05)))
        draw.line([cx0, cy0, cx0, cy0 + sy * l], fill=hexa(SAVANNA_SUN), width=max(5, int(s * 0.05)))


def glyph_sensor(draw, cx, cy, s):
    """Probe rod in soil with a readout node."""
    draw.rectangle([cx - s * 0.10, cy - s * 0.5, cx + s * 0.10, cy - s * 0.02],
                   fill=hexa(BONE_WHITE), outline=hexa(HIGHLAND_GREEN),
                   width=max(3, int(s * 0.025)))
    draw.ellipse([cx - s * 0.16, cy - s * 0.62, cx + s * 0.16, cy - s * 0.30],
                 fill=hexa(SOFT_GREEN))
    # soil band
    draw.rounded_rectangle([cx - s * 0.6, cy - s * 0.02, cx + s * 0.6, cy + s * 0.42],
                           radius=s * 0.12, fill=hexa(RICH_EARTH, 160))
    # waves
    for i in range(3):
        y = cy + s * 0.10 + i * s * 0.11
        draw.arc([cx - s * 0.22, y - s * 0.05, cx + s * 0.22, y + s * 0.05],
                 start=0, end=180, fill=hexa(SAVANNA_SUN), width=max(3, int(s * 0.02)))


def glyph_phone(draw, cx, cy, s):
    """Phone with a growing leaf on screen."""
    draw.rounded_rectangle([cx - s * 0.30, cy - s * 0.62, cx + s * 0.30, cy + s * 0.62],
                           radius=s * 0.08, fill=hexa(BONE_WHITE),
                           outline=hexa(HIGHLAND_GREEN), width=max(5, int(s * 0.045)))
    draw.rounded_rectangle([cx - s * 0.22, cy - s * 0.52, cx + s * 0.22, cy + s * 0.52],
                           radius=s * 0.05, fill=hexa(SKY_WASH))
    leaf = Image.new("RGBA", (int(s * 0.6), int(s * 0.6)), (0, 0, 0, 0))
    ld = ImageDraw.Draw(leaf)
    ld.ellipse([s * 0.06, s * 0.10, s * 0.56, s * 0.62], fill=hexa(HIGHLAND_GREEN))
    leaf = leaf.rotate(-30, resample=Image.BICUBIC)
    draw.bitmap((int(cx - s * 0.30), int(cy - s * 0.30)), leaf, None)
    # home button
    draw.ellipse([cx - s * 0.05, cy + s * 0.50, cx + s * 0.05, cy + s * 0.56],
                 fill=hexa(HIGHLAND_GREEN))


def glyph_chart(draw, cx, cy, s):
    """Rising price line with an up arrow + coin."""
    draw.line([cx - s * 0.6, cy + s * 0.4, cx - s * 0.55, cy + s * 0.35], fill=hexa(RICH_EARTH, 120),
              width=max(3, int(s * 0.02)))
    draw.line([cx - s * 0.6, cy - s * 0.4, cx - s * 0.6, cy + s * 0.4], fill=hexa(RICH_EARTH, 160),
              width=max(3, int(s * 0.02)))
    pts = [(cx - s * 0.6, cy + s * 0.32), (cx - s * 0.35, cy + s * 0.18),
           (cx - s * 0.1, cy - s * 0.1), (cx + s * 0.15, cy - s * 0.3),
           (cx + s * 0.4, cy - s * 0.52)]
    draw.line(pts, fill=hexa(HIGHLAND_GREEN), width=max(6, int(s * 0.05)))
    for px, py in pts:
        draw.ellipse([px - s * 0.035, py - s * 0.035, px + s * 0.035, py + s * 0.035],
                     fill=hexa(SAVANNA_SUN))
    # coin
    draw.ellipse([cx - s * 0.16, cy - s * 0.75, cx + s * 0.16, cy - s * 0.43],
                 fill=hexa(HARVEST_GOLD), outline=hexa(SAVANNA_SUN),
                 width=max(3, int(s * 0.02)))
    draw.text((cx - s * 0.09, cy - s * 0.68), "K", font=font(int(s * 0.22)), fill=hexa(BONE_WHITE))

GLYPHS = {
    "kale": glyph_kale,
    "tomato": glyph_tomato,
    "french-beans": glyph_beans,
    "mango": glyph_mango,
    "bulb-onion": glyph_onion,
    "drip": glyph_drip,
    "scan": glyph_scan,
    "sensor": glyph_sensor,
    "phone": glyph_phone,
    "chart": glyph_chart,
}

HEADER_META = {
    "sukuma-wiki-kale-growing-guide": ("kale", "Kale", "Sukuma wiki"),
    "tomato-economics-varieties-spray": ("tomato", "Tomato", "Nyanya"),
    "french-beans-export-cold-chain": ("french-beans", "French beans", "Mishiri"),
    "mango-meru-varieties-13c-rule": ("mango", "Mango", "Embe"),
    "bulb-onion-storage-curing-guide": ("bulb-onion", "Bulb onion", "Kitunguu"),
    "drip-irrigation-cost-kenya-2026": ("drip", "Drip irrigation", "Maji kidogo kidogo"),
    "panda-disease-scanner-how-it-works": ("scan", "Disease scanner", "Piga picha, pata jibu"),
    "soil-moisture-sensors-smallholders": ("sensor", "Soil sensors", "Unyevu wa udongo"),
    "offline-first-phone-kenya": ("phone", "Offline-first", "Simu yako inatosha"),
    "market-price-data-harvest-timing": ("chart", "Market prices", "Jua bei kabla ya kuvuna"),
}


# ─── Generators ───────────────────────────────────────────────────────────────

def generate_og_image():
    img = Image.new("RGBA", OG_SIZE, hexa(PANDA_DAWN))
    d = ImageDraw.Draw(img)
    sun_disc(d, 985, 150, 130, SAVANNA_SUN, 70)
    ground_band(d, *OG_SIZE, 430, 470, SOFT_GREEN, 50)
    ground_band(d, *OG_SIZE, 470, 630, HIGHLAND_GREEN, 30)
    # scattered leaf marks
    for x, y, sc in [(180, 520, 0.8), (240, 470, 0.6), (1080, 500, 0.9), (1140, 430, 0.55)]:
        draw_leaf_dot(d, x, y, 26 * sc)
    # headline
    d.text((90, 150), "Plan, log, and grow", font=font(64), fill=hexa(HIGHLAND_GREEN))
    d.text((90, 225), "your horticulture farm.", font=font(64), fill=hexa(HIGHLAND_GREEN))
    d.text((90, 330), "Season plans for 17 crops · disease scanner · market prices",
           font=font(30, italic=True), fill=hexa(RICH_EARTH, 200))
    # wordmark
    wordmark(d, 90, 520, size=44, fill=HIGHLAND_GREEN, dot=PANDA_LEAF)
    d.text((90, 572), "Inspired by SHEP PLUS · Bilingual EN / SW · Meru + Kirinyaga pilot",
           font=font(24), fill=hexa(RICH_EARTH, 170))
    out = PUBLIC_DIR / "og-image.png"
    img.convert("RGB").save(out)
    print("wrote", out)


def generate_headers():
    for slug, (crop, en, sw) in HEADER_META.items():
        img = Image.new("RGBA", HEADER_SIZE, hexa(PANDA_DAWN))
        d = ImageDraw.Draw(img)
        sun_disc(d, 320, 210, 150, SAVANNA_SUN, 60)
        ground_band(d, *HEADER_SIZE, 640, 680, SOFT_GREEN, 55)
        ground_band(d, *HEADER_SIZE, 680, 900, HIGHLAND_GREEN, 28)
        # crop glyph, left half
        GLYPHS[crop](d, 420, 480, 400)
        # text block, right half
        d.text((830, 260), "Panda crop guide", font=font(34, italic=True), fill=hexa(TERRACOTTA))
        d.text((830, 320), en, font=font(96), fill=hexa(HIGHLAND_GREEN))
        d.text((830, 430), sw, font=font(44, italic=True), fill=hexa(RICH_EARTH, 180))
        d.text((830, 560), "Field numbers, not guesswork.", font=font(32), fill=hexa(RICH_EARTH, 200))
        wordmark(d, 830, 760, size=40, fill=HIGHLAND_GREEN, dot=PANDA_LEAF)
        out = PUBLIC_DIR / "blog" / f"{slug}.png"
        out.parent.mkdir(parents=True, exist_ok=True)
        img.convert("RGB").save(out)
        print("wrote", out)


def generate_logo():
    size = (900, 260)
    img = Image.new("RGBA", size, (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    draw_leaf_mark(d, 130, 130, 190, fill=HIGHLAND_GREEN, accent=SAVANNA_SUN)
    wordmark(d, 270, 62, size=96, fill=HIGHLAND_GREEN, dot=PANDA_LEAF)
    d.text((270, 172), "Plan. Log. Grow.", font=font(34, italic=True), fill=hexa(SAVANNA_SUN))
    out = PUBLIC_DIR / "logo-horizontal.png"
    img.save(out)
    print("wrote", out)


def generate_favicon():
    svg = f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <rect width="64" height="64" rx="14" fill="{PANDA_DAWN_H}"/>
  <circle cx="32" cy="24" r="7" fill="{SAVANNA_SUN_H}"/>
  <path d="M32 34 C 20 30 16 42 16 52 C 16 58 24 60 32 60 C 40 60 48 58 48 52 C 48 42 44 30 32 34 Z" fill="{HIGHLAND_GREEN_H}"/>
  <path d="M32 34 C 25 32 22 38 22 46 C 22 52 26 56 32 58 C 38 56 42 52 42 46 C 42 38 39 32 32 34 Z" fill="{PANDA_LEAF_H}"/>
</svg>
"""
    out = PUBLIC_DIR / "favicon.svg"
    out.write_text(svg)
    print("wrote", out)


def _h(color):
    return "#%02x%02x%02x" % color


PANDA_DAWN_H = _h(PANDA_DAWN)
SAVANNA_SUN_H = _h(SAVANNA_SUN)
HIGHLAND_GREEN_H = _h(HIGHLAND_GREEN)
PANDA_LEAF_H = _h(PANDA_LEAF)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--all", action="store_true")
    ap.add_argument("--og", action="store_true")
    ap.add_argument("--headers", action="store_true")
    ap.add_argument("--logo", action="store_true")
    ap.add_argument("--favicon", action="store_true")
    args = ap.parse_args()
    do_all = args.all
    if do_all or args.logo:
        generate_logo()
    if do_all or args.favicon:
        generate_favicon()
    if do_all or args.og:
        generate_og_image()
    if do_all or args.headers:
        generate_headers()


if __name__ == "__main__":
    main()
