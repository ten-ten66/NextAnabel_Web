#!/usr/bin/env python3
"""qa-output/thumbs/*.png をサンプル集用の WebP に縮小して sites/hub/assets/img/ に保存する。"""
from pathlib import Path
from PIL import Image

root = Path(__file__).resolve().parents[2]
src = root / 'qa-output' / 'thumbs'
dst = root / 'sites' / 'hub' / 'assets' / 'img'
dst.mkdir(parents=True, exist_ok=True)
sizes = {'desktop': (960, 600), 'mobile': (240, 520)}
for png in sorted(src.glob('*.png')):
    kind = png.stem.rsplit('-', 1)[1]
    w, h = sizes[kind]
    img = Image.open(png).convert('RGB')
    img = img.resize((w, round(img.height * w / img.width)), Image.LANCZOS).crop((0, 0, w, h))
    out = dst / f'thumb-{png.stem}.webp'
    img.save(out, 'WEBP', quality=82, method=6)
    print(out.relative_to(root), f'{out.stat().st_size // 1024}KB')
