#!/usr/bin/env python3
"""Turn the raw admin captures into Divi Marketplace screenshots.

Two things happen here.

Framing: the WordPress admin bar and menu are cropped away so each shot is just
the plugin panel, and every result is written at exactly 1425x796 to match the
aspect ratio of the marketplace featured image. The crop window is a fixed
1378x770 slice of the source (the panel is 1378px wide), so the UI is rendered
at one consistent scale across the whole set; the 3.4% upscale to 1425 is below
the point where text softens. Panels shorter than the window are centred on the
WordPress page background instead of being stretched.

Content: the captures come from a Freemius-enabled dev install, so the "Pro"
tier badge and the "License" button — neither of which exists in the marketplace
edition — are painted out of the plugin header.

Re-run this after re-capturing rather than editing the PNGs by hand.
"""
from PIL import Image
from pathlib import Path

SRC = Path("/home/shakhawat/Videos/product assets/screenshots")
OUT = Path(__file__).resolve().parent.parent / "marketplace-assets" / "screenshots"

TARGET = (1425, 796)

PANEL_X0, PANEL_X1 = 182, 1560      # plugin panel, border to border
WINDOW_W = PANEL_X1 - PANEL_X0      # 1378
WINDOW_H = round(WINDOW_W * TARGET[1] / TARGET[0])   # 770
PAGE_BG = (240, 240, 240)           # WordPress admin page background

HEADER_BG   = (255, 255, 255)
PRO_BADGE   = (620,  84,  688, 116)   # "Pro" pill next to the version chip
LICENSE_BTN = (1172, 76, 1288, 124)   # "License" button, left of "Reset"
PRO_PROBE   = (650, 100)              # non-white here => header has the badge

PANEL_TOP = 52

# source file -> (marketplace number, top of the crop window).
#
# `None` means the panel is shorter than the window, so it gets centred on the
# page background. The builder shots start at 135 rather than the panel top:
# that drops the thin "Add new / Save changes" strip and in exchange the window
# reaches the card's bottom edge at 905, framing the whole builder.
ORDER = [
    ("shortcode builder.png",             1, 135),
    ("shortcode builder grid layout.png", 2, 135),
    ("shortcode list page.png",           3, None),
    ("storefront settings.png",           4, None),
    ("appearance settings.png",           5, PANEL_TOP),
    ("recording modal settings.png",      6, PANEL_TOP),
    ("email reminder settings.png",       7, PANEL_TOP),
]


def strip_header_chrome(im):
    """Blank the Pro badge and License button out of the plugin header."""
    if sum(abs(a - b) for a, b in zip(im.getpixel(PRO_PROBE), HEADER_BG)) < 12:
        return False
    im.paste(HEADER_BG, PRO_BADGE)
    im.paste(HEADER_BG, LICENSE_BTN)
    return True


def panel_bottom(im):
    """Row of the panel's bottom border, found by its border colour."""
    border = im.getpixel((1000, PANEL_TOP))
    rows = [y for y in range(PANEL_TOP, im.size[1]) if im.getpixel((1000, y)) == border]
    return rows[-1]


def frame(im, top):
    """Crop the 1378x770 window, centring the panel if it is shorter."""
    if top is not None:
        return im.crop((PANEL_X0, top, PANEL_X1, top + WINDOW_H)), "crop"

    bottom = panel_bottom(im)
    panel = im.crop((PANEL_X0, PANEL_TOP, PANEL_X1, bottom + 1))
    canvas = Image.new("RGB", (WINDOW_W, WINDOW_H), PAGE_BG)
    canvas.paste(panel, (0, (WINDOW_H - panel.size[1]) // 2))
    return canvas, "centred"


for name, number, top in ORDER:
    im = Image.open(SRC / name).convert("RGB")
    header_edited = strip_header_chrome(im)
    window, how = frame(im, top)
    shot = window.resize(TARGET, Image.LANCZOS)
    shot.save(OUT / f"screenshot-{number}.png", "PNG", optimize=True)
    print(f"screenshot-{number}.png  {shot.size[0]}x{shot.size[1]}  {how:8s} "
          f"header={'cleaned' if header_edited else 'unchanged'}  <- {name}")
