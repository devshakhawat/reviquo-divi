#!/usr/bin/env python3
"""Composite marketing copy into the empty left column of the featured image."""

from PIL import Image, ImageDraw, ImageFont

SRC = "product-featured-clean.jpg"  # pristine artwork; never drawn on
OUT = "product-featured-1426x796.jpg"
PNG = "product-featured-with-text.png"

BOLD = "/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf"
REG = "/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf"

SS = 2  # supersample factor for the text layer

WHITE = (255, 255, 255, 255)
MINT = (82, 214, 172, 255)
MUTED = (170, 179, 214, 255)

X = 78          # left margin of the text column
MAXW = 492      # usable width before the artwork starts

EYEBROW = "FOR DIVI & WOOCOMMERCE"
HEAD = ["Video & Photo", "Product Reviews"]
TAG = [
    "Let customers record webcam videos and upload",
    "photos directly in your WooCommerce review form.",
]
BULLETS = [
    "Native Divi 4 & Divi 5 modules",
    "Rating summary, filters & verified badges",
    "Automated review reminder emails",
]


def font(path, size):
    return ImageFont.truetype(path, size * SS)


def width(draw, text, f, tracking=0):
    w = draw.textlength(text, font=f)
    return w + tracking * SS * max(0, len(text) - 1)


def fit(draw, lines, path, start, maxw):
    """Largest size at which every line fits the column."""
    size = start
    while size > 12:
        f = font(path, size)
        if all(width(draw, t, f) <= maxw * SS for t in lines):
            return size, f
        size -= 1
    return size, font(path, size)


def tracked(draw, xy, text, f, fill, tracking):
    x, y = xy
    for ch in text:
        draw.text((x, y), ch, font=f, fill=fill)
        x += draw.textlength(ch, font=f) + tracking * SS


def check(draw, cx, cy, r):
    draw.ellipse([cx - r, cy - r, cx + r, cy + r], fill=MINT)
    w = max(2, int(r * 0.30))
    draw.line(
        [(cx - r * 0.42, cy + r * 0.02), (cx - r * 0.10, cy + r * 0.36),
         (cx + r * 0.46, cy - r * 0.34)],
        fill=(10, 22, 45, 255), width=w, joint="curve",
    )


base = Image.open(SRC).convert("RGB")
W, H = base.size
layer = Image.new("RGBA", (W * SS, H * SS), (0, 0, 0, 0))
d = ImageDraw.Draw(layer)

f_eye = font(BOLD, 17)
head_size, f_head = fit(d, HEAD, BOLD, 56, MAXW)
tag_size, f_tag = fit(d, TAG, REG, 21, MAXW)
bul_size, f_bul = fit(d, BULLETS, REG, 20, MAXW - 40)

line_h = int(head_size * 1.16)

# Measured block height, so the copy sits centred against the artwork.
block = 17 + 24 + line_h * len(HEAD) + 20 + int(tag_size * 1.42) * len(TAG) + 34 + 40 * len(BULLETS)
y = max(120, int(395 - block / 2))

tracked(d, (X * SS, y * SS), EYEBROW, f_eye, MINT, 2.6)
y += 17 + 26

for line in HEAD:
    d.text((X * SS, y * SS), line, font=f_head, fill=WHITE)
    y += line_h
y += 20

for line in TAG:
    d.text((X * SS, y * SS), line, font=f_tag, fill=MUTED)
    y += int(tag_size * 1.42)
y += 34

for line in BULLETS:
    check(d, (X + 11) * SS, (y + bul_size * 0.62) * SS, 11 * SS)
    d.text(((X + 34) * SS, y * SS), line, font=f_bul, fill=(226, 231, 248, 255))
    y += 40

print(f"headline {head_size}px  tagline {tag_size}px  bullets {bul_size}px  block ends y={y}")
assert y < 690, f"text overruns the safe zone (y={y})"

layer = layer.resize((W, H), Image.LANCZOS)
out = base.convert("RGBA")
out.alpha_composite(layer)
out = out.convert("RGB")

out.save(PNG)
out.save(OUT, "JPEG", quality=95, subsampling=0, optimize=True, progressive=False)
print(f"wrote {OUT} and {PNG} at {out.size}")
