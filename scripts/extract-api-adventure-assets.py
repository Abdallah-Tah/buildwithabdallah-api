#!/usr/bin/env python3
"""
Cut the generated sprite sheets into the individual assets the adventure uses.

Development tooling. Nothing here runs at request time: it reads the source
atlases once and writes the production sprites into public/images/api-adventure.

The sheets already carry a real alpha channel, so no background removal is
needed or wanted — many of these objects are deliberately black metal, and
keying on darkness would eat them. Segmentation is purely on alpha.

Each sprite is found by labelling connected regions of "not transparent",
dilated first so a glow halo stays attached to the object it belongs to and
two halves of one machine do not come back as two sprites.

Usage:
    python3 scripts/extract-api-adventure-assets.py --survey   # index the sheets
    python3 scripts/extract-api-adventure-assets.py            # write assets
"""

from __future__ import annotations

import argparse
import json
import pathlib
import sys

import numpy as np
from PIL import Image
from scipy import ndimage

ROOT = pathlib.Path(__file__).resolve().parent.parent
# Kept out of public/ deliberately: these are 9MB of build input, not
# something a visitor should ever be able to download.
SOURCE = ROOT / "resources/sprite-sources"
OUT = ROOT / "public/images/api-adventure"

# Dilation is per sheet because the sheets differ in how much glow bleeds
# between neighbours. Two stations whose halos touch label as one region at
# dilate 6 but separate cleanly at 2, while the runner's cape needs 3 to stay
# attached to the runner.
SHEETS = {
    "runner": ("neon_blue_platformer_sprite_sheet.png", 3),
    "pipes": ("sci_fi_neon_pipe_asset_atlas.png", 2),
    "stations": ("neon_cyberpunk_pipeline_ui_asset_sheet.png", 2),
    "services": ("neon_sci_fi_service_station_asset_sheet.png", 2),
    "robot": ("neon_ai_robot_game_ui_sprite_sheet.png", 2),
    "environment": ("cyber_factory_modular_sprite_sheet.png", 3),
}

# Alpha at or below this is background. Kept low so faint glow is preserved.
ALPHA_FLOOR = 12

# Regions smaller than this are noise from the generator's halo fringing.
MIN_AREA = 2600


def regions(sheet: Image.Image, dilate: int) -> list[tuple[int, int, int, int]]:
    """Bounding boxes of every distinct sprite, left-to-right, top-to-bottom."""
    alpha = np.array(sheet.getchannel("A"))
    mask = alpha > ALPHA_FLOOR

    # Bridge the gap between a sprite and its own glow, and between parts of one
    # machine, without merging neighbours that are genuinely separate.
    if dilate:
        mask = ndimage.binary_dilation(mask, iterations=dilate)

    labels, count = ndimage.label(mask)
    boxes = []

    for ys, xs in ndimage.find_objects(labels):
        if (ys.stop - ys.start) * (xs.stop - xs.start) < MIN_AREA:
            continue
        # Undo the dilation so the box hugs the real pixels again.
        boxes.append((
            max(xs.start + dilate, 0),
            max(ys.start + dilate, 0),
            min(xs.stop - dilate, sheet.width),
            min(ys.stop - dilate, sheet.height),
        ))

    del count

    # Row-major order, with a tolerance so a row of sprites at slightly
    # different heights still reads left to right.
    boxes.sort(key=lambda b: (round(b[1] / 90), b[0]))

    return boxes


def trim(image: Image.Image) -> Image.Image:
    """Drop fully transparent margins."""
    box = image.getchannel("A").point(lambda v: 255 if v > ALPHA_FLOOR else 0).getbbox()

    return image.crop(box) if box else image


def survey() -> None:
    """Write a numbered contact sheet per atlas so regions can be identified."""
    OUT.mkdir(parents=True, exist_ok=True)
    index = {}

    for name, (filename, sheet_dilate) in SHEETS.items():
        sheet = Image.open(SOURCE / filename).convert("RGBA")
        boxes = regions(sheet, sheet_dilate)
        index[name] = [list(b) for b in boxes]

        # Draw the boxes over a dark backing so the sprites stay visible.
        from PIL import ImageDraw

        canvas = Image.new("RGBA", sheet.size, (11, 17, 32, 255))
        canvas.alpha_composite(sheet)
        draw = ImageDraw.Draw(canvas)

        for i, (x0, y0, x1, y1) in enumerate(boxes):
            draw.rectangle([x0, y0, x1, y1], outline=(255, 60, 60, 255), width=3)
            draw.text((x0 + 6, y0 + 4), str(i), fill=(255, 255, 0, 255))

        canvas.convert("RGB").save(OUT / f"_survey-{name}.png", quality=90)
        print(f"{name:12s} {len(boxes):3d} regions -> _survey-{name}.png")

    (OUT / "_survey.json").write_text(json.dumps(index, indent=1))


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--survey", action="store_true", help="index regions only")
    args = parser.parse_args()

    if args.survey:
        survey()

        return 0

    from extract_map import write_assets  # noqa: PLC0415

    write_assets(SHEETS, SOURCE, OUT, regions, trim)

    return 0


if __name__ == "__main__":
    sys.exit(main())
