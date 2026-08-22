"""
Which region of which sheet becomes which production asset.

Indices refer to the numbered regions in the survey contact sheets, produced by
`extract-api-adventure-assets.py --survey`. Re-run the survey after changing a
source sheet: the indices are positional and will move.

Runner frames are chosen, not taken wholesale. Several generated poses are
inconsistent with their neighbours (a different cape length, a head turn) and
would read as a glitch at 12fps, so only coherent runs are used.
"""

from __future__ import annotations

import json
import pathlib

from PIL import Image

# Runner frames land on a fixed square canvas, feet on the baseline, so the
# character keeps one size and one ground contact across every state. Without
# this the sprite bounces as the tight crop changes shape frame to frame.
RUNNER_FRAME = 168
RUNNER_BASELINE = 6          # pixels of padding under the feet
RUNNER_TARGET_H = 150        # drawn height inside the frame

RUNNER_STATES = {
    "idle": [0, 1, 2, 3],
    "run": [8, 9, 10, 11, 12, 13, 14, 15],
    "jump": [16, 18, 19, 20],
    "enterPipe": [17, 21, 22],
    "exitPipe": [23, 25],
    "success": [27, 29, 30, 31],
}

# Single sprites: sheet -> {output name: region index}
SINGLES = {
    "pipes": {
        "pipes/horizontal": 0,
        "pipes/vertical": 3,
        "pipes/elbow-left-down": 1,
        "pipes/elbow-down-right": 2,
        "pipes/elbow-left-up": 4,
        "pipes/elbow-up-right": 5,
        "pipes/tee-up": 6,
        "pipes/cross": 7,
        "pipes/connector": 9,
        "pipes/cap": 12,
        "pipes/ring": 13,
    },
    "stations": {
        "stations/products": 0,
        "stations/signature": 1,
        "stations/central": 2,
        "portal/target": 3,
        "stations/signed-event": 4,
    },
    "services": {
        "stations/ai": 0,
        "stations/whatsapp": 1,
        "stations/billing": 2,
    },
    "robot": {
        "robot/idle": 2,
        "robot/wave": 3,
        "effects/orb": 7,
        "effects/crystal": 8,
        "effects/cube": 9,
        "effects/gem-green": 12,
        "effects/shield": 17,
        "effects/ripple": 18,
        "effects/burst": 19,
        "effects/check": 21,
        "effects/spark": 28,
    },
    "environment": {
        "environment/platform-wide": 5,
        "environment/platform": 1,
        "environment/server-rack": 31,
        "environment/smoke": 16,
        "environment/spark-blue": 26,
        "environment/beacon": 10,
        "environment/flag": 11,
    },
}

# Wider than this and the sprite is a backdrop, not an object: cap it so a
# 1400px atlas region does not ship as a 1400px asset for a 240px slot.
MAX_WIDTH = {
    "stations/signed-event": 900,
    "environment/server-rack": 260,
    "environment/platform-wide": 640,
}
DEFAULT_MAX_WIDTH = 520


def _fit(image: Image.Image, name: str) -> Image.Image:
    limit = MAX_WIDTH.get(name, DEFAULT_MAX_WIDTH)

    if image.width <= limit:
        return image

    height = round(image.height * limit / image.width)

    return image.resize((limit, height), Image.LANCZOS)


def _runner_strip(sheet: Image.Image, boxes, indices, trim) -> Image.Image:
    """One horizontal strip of fixed-size frames, feet aligned to a baseline."""
    strip = Image.new("RGBA", (RUNNER_FRAME * len(indices), RUNNER_FRAME), (0, 0, 0, 0))

    for slot, index in enumerate(indices):
        sprite = trim(sheet.crop(boxes[index]))
        scale = RUNNER_TARGET_H / sprite.height
        size = (max(1, round(sprite.width * scale)), RUNNER_TARGET_H)
        sprite = sprite.resize(size, Image.LANCZOS)

        strip.alpha_composite(
            sprite,
            (
                slot * RUNNER_FRAME + (RUNNER_FRAME - sprite.width) // 2,
                RUNNER_FRAME - RUNNER_BASELINE - sprite.height,
            ),
        )

    return strip


def _sign_board(sprite: Image.Image) -> dict:
    """
    Find the blank sign board on a station, as fractions of the sprite.

    The boards are intentionally empty artwork — the label goes on them as HTML
    — so the overlay has to know where they are. Measuring beats hard-coding:
    the flag on the products machine pushes its board a third of the way down
    while the others sit near the top, and a regenerated sheet would move them
    again.

    Scans from the top for the first band wide enough to be a board rather than
    a flag pole or a mounting post, ending where it narrows back to the post.
    """
    import numpy as np  # noqa: PLC0415

    opaque = np.array(sprite.getchannel("A")) > 40
    width = opaque.sum(axis=1) / sprite.width
    start = end = None

    for y, fraction in enumerate(width):
        if start is None and fraction > 0.42:
            start = y
        elif start is not None and fraction < 0.24:
            end = y
            break

    if start is None:
        return {"top": 0.2, "width": 0.58}

    end = end if end is not None else sprite.height

    return {
        "top": round((start + end) / 2 / sprite.height, 4),
        # Inset from the board's own width so the text never touches the frame.
        "width": round(float(width[start:end].max()) * 0.86, 4),
    }


def _tileable_body(horizontal: Image.Image) -> Image.Image:
    """
    A repeatable centre slice of the straight pipe.

    Long runs tile this instead of stretching one 520px image across the whole
    width, which is what makes a stretched pipe read as a rubber band. The
    slice is taken from the middle third, clear of both end flanges, and is
    narrow enough that the light seam in the source does not repeat visibly.
    """
    x0 = round(horizontal.width * 0.42)

    return horizontal.crop((x0, 0, x0 + 48, horizontal.height))


def write_assets(sheets, source, out, regions, trim) -> None:
    manifest: dict = {"runner": {}, "frame": RUNNER_FRAME}
    loaded = {}

    for name, (filename, dilate) in sheets.items():
        sheet = Image.open(source / filename).convert("RGBA")
        loaded[name] = (sheet, regions(sheet, dilate))

    # Runner: one strip per state.
    sheet, boxes = loaded["runner"]
    (out / "runner").mkdir(parents=True, exist_ok=True)

    for state, indices in RUNNER_STATES.items():
        usable = [i for i in indices if i < len(boxes)]
        strip = _runner_strip(sheet, boxes, usable, trim)
        path = out / f"runner/{state}.webp"
        strip.save(path, "WEBP", quality=92, method=6)
        manifest["runner"][state] = {
            "src": f"/images/api-adventure/runner/{state}.webp",
            "frames": len(usable),
        }
        print(f"  runner/{state}.webp  {len(usable)} frames  {path.stat().st_size // 1024}KB")

    # Everything else: one file per sprite.
    for sheet_name, wanted in SINGLES.items():
        sheet, boxes = loaded[sheet_name]

        for asset, index in wanted.items():
            if index >= len(boxes):
                print(f"  !! {asset}: region {index} missing from {sheet_name}")
                continue

            sprite = _fit(trim(sheet.crop(boxes[index])), asset)
            path = out / f"{asset}.webp"
            path.parent.mkdir(parents=True, exist_ok=True)
            sprite.save(path, "WEBP", quality=92, method=6)

            group, key = asset.split("/")
            entry = {
                "src": f"/images/api-adventure/{asset}.webp",
                "w": sprite.width,
                "h": sprite.height,
            }

            if group == "stations" and key != "signed-event":
                entry["sign"] = _sign_board(sprite)

            manifest.setdefault(group, {})[key] = entry
            print(f"  {asset}.webp  {sprite.width}x{sprite.height}  {path.stat().st_size // 1024}KB")

    # Derived from the straight pipe rather than cropped from the atlas.
    horizontal = Image.open(out / "pipes/horizontal.webp")
    body = _tileable_body(horizontal)
    body.save(out / "pipes/body.webp", "WEBP", quality=94, method=6)
    manifest["pipes"]["body"] = {
        "src": "/images/api-adventure/pipes/body.webp",
        "w": body.width,
        "h": body.height,
    }
    print(f"  pipes/body.webp  {body.width}x{body.height} (tileable)")

    # Tinting multiplies, so a blue orb times amber comes out green. The
    # packet ships as luminance and takes its colour from the stage instead.
    orb = Image.open(out / "effects/orb.webp").convert("RGBA")
    grey = orb.convert("L").point(lambda v: min(255, int(v * 1.25)))
    neutral = Image.merge("RGBA", (grey, grey, grey, orb.getchannel("A")))
    neutral.save(out / "effects/orb-neutral.webp", "WEBP", quality=94, method=6)
    manifest["effects"]["orbNeutral"] = {
        "src": "/images/api-adventure/effects/orb-neutral.webp",
        "w": neutral.width,
        "h": neutral.height,
    }
    print(f"  effects/orb-neutral.webp  {neutral.width}x{neutral.height} (tintable)")

    vertical = Image.open(out / "pipes/vertical.webp")
    y0 = round(vertical.height * 0.42)
    body_v = vertical.crop((0, y0, vertical.width, y0 + 48))
    body_v.save(out / "pipes/body-vertical.webp", "WEBP", quality=94, method=6)
    manifest["pipes"]["bodyVertical"] = {
        "src": "/images/api-adventure/pipes/body-vertical.webp",
        "w": body_v.width,
        "h": body_v.height,
    }
    print(f"  pipes/body-vertical.webp  {body_v.width}x{body_v.height} (tileable)")

    (out / "manifest.json").write_text(json.dumps(manifest, indent=2))

    # The runtime reads an ES module rather than fetching JSON: the public site
    # has no build step, and one more network round trip before the first frame
    # is worse than a few hundred bytes of generated source.
    module = pathlib.Path(str(out).replace("public/images", "public/js")).parent / "api-adventure/assets.js"
    module.parent.mkdir(parents=True, exist_ok=True)
    module.write_text(
        "/* Generated by scripts/extract-api-adventure-assets.py. Do not edit. */\n\n"
        "export const adventureAssets = "
        + json.dumps(manifest, indent=4).replace('"', "'").replace("'", "'")
        + ";\n"
    )
    print(f"\nmanifest -> {out / 'manifest.json'}")
    print(f"module   -> {module}")
