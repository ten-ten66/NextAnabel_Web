#!/usr/bin/env python3
"""オリジナルビジュアルの生成

写真素材を使わずに、各サイトのキービジュアル・テクスチャを数式で描画して WebP に書き出す。
乱数のシードを固定しているため、何度実行しても同じ画像になる。

    python3 tools/gen_visuals.py                  # 全サイト
    python3 tools/gen_visuals.py skin-clinic lp   # 指定サイトのみ
"""

from __future__ import annotations

import math
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw

ROOT = Path(__file__).resolve().parent.parent


# ---------------------------------------------------------------------------
# 基本関数
# ---------------------------------------------------------------------------

def rgb(hex_code: str) -> np.ndarray:
    h = hex_code.lstrip('#')
    return np.array([int(h[i:i + 2], 16) for i in (0, 2, 4)], dtype=np.float32) / 255.0


def smoothstep(e0: float, e1: float, x: np.ndarray) -> np.ndarray:
    t = np.clip((x - e0) / (e1 - e0), 0.0, 1.0)
    return t * t * (3.0 - 2.0 * t)


def mix(a, b, t):
    return a * (1.0 - t) + b * t


def grid(h: int, w: int) -> tuple[np.ndarray, np.ndarray]:
    yy, xx = np.mgrid[0:h, 0:w].astype(np.float32)
    return xx, yy


def noise(h: int, w: int, cells: float, rng: np.random.Generator) -> np.ndarray:
    """値ノイズ（乱数格子を双三次補間で拡大）。cells は短辺方向の格子数。"""
    short = min(h, w)
    gh = max(2, int(round(cells * h / short))) + 1
    gw = max(2, int(round(cells * w / short))) + 1
    lattice = rng.random((gh, gw)).astype(np.float32)
    img = Image.fromarray(lattice, 'F').resize((w, h), Image.BICUBIC)
    return np.asarray(img, dtype=np.float32)


def fbm(h: int, w: int, cells: float, rng: np.random.Generator, octaves: int = 5, gain: float = 0.5) -> np.ndarray:
    total = np.zeros((h, w), np.float32)
    amp, norm = 1.0, 0.0
    for octave in range(octaves):
        total += amp * noise(h, w, cells * 2 ** octave, rng)
        norm += amp
        amp *= gain
    total /= norm
    return (total - total.min()) / (total.max() - total.min() + 1e-6)


def sample(field: np.ndarray, x: np.ndarray, y: np.ndarray) -> np.ndarray:
    """双線形補間でのサンプリング（範囲外は端の値）"""
    h, w = field.shape
    x = np.clip(x, 0, w - 1.001)
    y = np.clip(y, 0, h - 1.001)
    x0 = np.floor(x).astype(np.int32)
    y0 = np.floor(y).astype(np.int32)
    fx, fy = x - x0, y - y0
    a, b = field[y0, x0], field[y0, x0 + 1]
    c, d = field[y0 + 1, x0], field[y0 + 1, x0 + 1]
    return (a * (1 - fx) + b * fx) * (1 - fy) + (c * (1 - fx) + d * fx) * fy


def warp(field: np.ndarray, rng: np.random.Generator, strength: float, cells: float) -> np.ndarray:
    """ドメインワーピング（座標をノイズでずらして流れるような模様にする）"""
    h, w = field.shape
    dx = fbm(h, w, cells, rng, 4) - 0.5
    dy = fbm(h, w, cells, rng, 4) - 0.5
    xx, yy = grid(h, w)
    return sample(field, xx + dx * strength, yy + dy * strength)


def _box_blur(a: np.ndarray, r: int, axis: int) -> np.ndarray:
    pad = [(0, 0), (0, 0)]
    pad[axis] = (r + 1, r)
    c = np.cumsum(np.pad(a, pad, mode='edge'), axis=axis, dtype=np.float64)
    out = c[2 * r + 1:, :] - c[:-2 * r - 1, :] if axis == 0 else c[:, 2 * r + 1:] - c[:, :-2 * r - 1]
    return (out / (2 * r + 1)).astype(np.float32)


def blur(mask: np.ndarray, radius: float) -> np.ndarray:
    """ガウスぼかしの近似（箱型ぼかし3回）。浮動小数のまま処理して階調の段差を出さない。"""
    r = max(1, int(round((math.sqrt(12 * radius * radius / 3 + 1) - 1) / 2)))
    out = mask.astype(np.float32)
    for _ in range(3):
        out = _box_blur(_box_blur(out, r, 0), r, 1)
    return out


def to_image(color: np.ndarray, seed: int = 0) -> Image.Image:
    # 8bit化で縞（バンディング）が出ないよう、ごく弱いディザを加える
    dither = np.random.default_rng(seed).random(color.shape[:2]).astype(np.float32)[..., None] - 0.5
    out = np.clip(color * 255.0 + dither * 0.9, 0, 255)
    return Image.fromarray((out + 0.5).astype(np.uint8), 'RGB')


def save(img: Image.Image, site: str, name: str, quality: int = 80) -> None:
    path = ROOT / 'sites' / site / 'assets' / 'img' / name
    path.parent.mkdir(parents=True, exist_ok=True)
    img.save(path, 'WEBP', quality=quality, method=6)
    print(f'  {path.relative_to(ROOT)}  {img.width}x{img.height}  {path.stat().st_size // 1024}KB')


def vignette(h: int, w: int, strength: float, cx: float = 0.5, cy: float = 0.5) -> np.ndarray:
    xx, yy = grid(h, w)
    d = np.sqrt(((xx / w - cx) * 1.2) ** 2 + (yy / h - cy) ** 2)
    return 1.0 - strength * smoothstep(0.35, 0.95, d)


# ---------------------------------------------------------------------------
# 描画レシピ
# ---------------------------------------------------------------------------

def glaze(w: int, h: int, seed: int, base: str, veil: str, tint: str, veil_amount: float = 0.16) -> np.ndarray:
    """磁器の釉薬のような、白地に淡い青と青磁色がにじんだ質感"""
    rng = np.random.default_rng(seed)
    n1 = warp(fbm(h, w, 1.1, rng, 4, 0.45), rng, w * 0.10, 0.9)
    n2 = warp(fbm(h, w, 1.6, rng, 4, 0.45), rng, w * 0.08, 1.2)
    xx, yy = grid(h, w)
    u, v = xx / w, yy / h
    color = np.ones((h, w, 3), np.float32) * rgb(base)
    # 右上に青がたまり、左下へ薄れていく
    direction = smoothstep(0.15, 1.05, u * 0.7 + (1 - v) * 0.6)
    pool = smoothstep(0.30, 0.95, n1) * direction
    color = mix(color, rgb(veil), (pool * veil_amount)[..., None])
    wash = smoothstep(0.25, 0.90, n2) * 0.50
    color = mix(color, rgb(tint), wash[..., None])
    sheen = np.exp(-((n2 - 0.55) ** 2) / 0.03) * 0.022
    color = color + sheen[..., None]
    return color * vignette(h, w, 0.04)[..., None]


def pearls(w: int, h: int, seed: int, bg_top: str, bg_bottom: str, items: list[tuple[float, float, float, str]]) -> np.ndarray:
    """柔らかな光の中に置いた真珠の静物"""
    rng = np.random.default_rng(seed)
    xx, yy = grid(h, w)
    v = yy / h
    color = mix(rgb(bg_top), rgb(bg_bottom), smoothstep(0.0, 1.0, v)[..., None])
    glow = np.exp(-(((xx / w - 0.3) ** 2) / 0.08 + ((v - 0.25) ** 2) / 0.12))
    color = color + glow[..., None] * 0.05
    soft = fbm(h, w, 2.0, rng, 3)
    color = color * (0.985 + soft[..., None] * 0.03)
    light = np.array([-0.45, -0.7, 0.55], np.float32)
    light /= np.linalg.norm(light)
    half = light + np.array([0, 0, 1], np.float32)
    half /= np.linalg.norm(half)
    for cx, cy, r, tint in items:
        cx, cy, r = cx * w, cy * h, r * min(w, h)
        shadow = ((xx - (cx + r * 0.22)) / (r * 1.15)) ** 2 + ((yy - (cy + r * 0.92)) / (r * 0.32)) ** 2 <= 1.0
        shade = blur(shadow.astype(np.float32), r * 0.32)
        color = color * (1.0 - shade[..., None] * 0.22)
        dx, dy = (xx - cx) / r, (yy - cy) / r
        rr = dx * dx + dy * dy
        inside = rr <= 1.0
        nz = np.sqrt(np.clip(1.0 - rr, 0.0, 1.0))
        diffuse = np.clip(dx * light[0] + dy * light[1] + nz * light[2], 0.0, 1.0)
        fresnel = (1.0 - nz) ** 2
        base = rgb(tint)
        iris = mix(rgb('#F3DCE6'), rgb('#D7ECEE'), np.clip(dx * 0.5 + 0.5, 0, 1)[..., None])
        sphere = base * (0.58 + 0.42 * diffuse[..., None]) + iris * (fresnel * 0.32)[..., None]
        ndh = np.clip(dx * half[0] + dy * half[1] + nz * half[2], 0.0, 1.0)
        sphere = sphere + (ndh ** 70 * 0.85 + ndh ** 9 * 0.12)[..., None]
        edge = np.clip((1.0 - np.sqrt(np.clip(rr, 0, None))) * r, 0.0, 1.0) * inside
        color = mix(color, sphere, edge[..., None])
    return color


def interior(w: int, h: int, seed: int, wall: str, floor: str, light: str, accent: str) -> np.ndarray:
    """ピントを外した室内（窓から差し込む光）。撮影写真に差し替える前提の画像枠用。"""
    rng = np.random.default_rng(seed)
    xx, yy = grid(h, w)
    u, v = xx / w, yy / h
    color = mix(rgb(wall), rgb(floor), smoothstep(0.62, 0.72, v)[..., None])
    color = color * (1.0 - 0.06 * v)[..., None]
    img = Image.new('L', (w, h), 0)
    draw = ImageDraw.Draw(img)
    for i in range(3):
        x0 = w * (0.08 + i * 0.13)
        draw.polygon([(x0, 0), (x0 + w * 0.08, 0), (x0 + w * 0.26, h * 0.68), (x0 + w * 0.12, h * 0.68)], fill=255)
    beams = blur(np.asarray(img, np.float32) / 255.0, w * 0.025)
    color = mix(color, rgb(light), (beams * 0.55)[..., None])
    shapes = Image.new('L', (w, h), 0)
    draw = ImageDraw.Draw(shapes)
    draw.rounded_rectangle([w * 0.62, h * 0.30, w * 0.80, h * 0.70], radius=int(w * 0.02), fill=255)
    draw.ellipse([w * 0.80, h * 0.18, w * 0.96, h * 0.55], fill=255)
    obj = blur(np.asarray(shapes, np.float32) / 255.0, w * 0.03)
    color = mix(color, rgb(accent), (obj * 0.35)[..., None])
    grain = fbm(h, w, 3.0, rng, 3)
    color = color * (0.98 + grain[..., None] * 0.04)
    return color * vignette(h, w, 0.12)[..., None]


def silk(w: int, h: int, seed: int, base: str, mid: str, rim: str, freq: float = 8.0) -> np.ndarray:
    """暗い絹の布のひだ（上から差すかすかな金属色の光）"""
    rng = np.random.default_rng(seed)
    xx, yy = grid(h, w)
    u, v = xx / w, yy / h
    flow = fbm(h, w, 1.3, rng, 4)
    d = u * 0.85 + v * 0.55 + (flow - 0.5) * 0.55
    height = (0.6 * np.sin(d * freq * math.tau / 3) + 0.28 * np.sin(d * freq * 2.1 + flow * 5.0)
              + 0.12 * np.sin(d * freq * 4.3 + flow * 9.0))
    gy, gx = np.gradient(height)
    k = min(w, h) * 0.9
    nx, ny, nz = -gx * k, -gy * k, np.ones_like(height)
    norm = np.sqrt(nx * nx + ny * ny + nz * nz)
    nx, ny, nz = nx / norm, ny / norm, nz / norm
    light = np.array([-0.55, -0.6, 0.58], np.float32)
    light /= np.linalg.norm(light)
    half = light + np.array([0, 0, 1], np.float32)
    half /= np.linalg.norm(half)
    diffuse = np.clip(nx * light[0] + ny * light[1] + nz * light[2], 0, 1)
    spec = np.clip(nx * half[0] + ny * half[1] + nz * half[2], 0, 1) ** 36
    color = mix(rgb(base), rgb(mid), (diffuse ** 2.2)[..., None])
    color = color + rgb(rim) * (spec * 0.55)[..., None]
    return color * vignette(h, w, 0.35, 0.45, 0.4)[..., None]


def linen(h: int, w: int, rng: np.random.Generator, base: str, amount: float = 0.035) -> np.ndarray:
    """麻布の織り目（縦横の繊維のむら）"""
    vertical = np.asarray(Image.fromarray(rng.random((8, w // 2)).astype(np.float32), 'F').resize((w, h), Image.BILINEAR))
    horizontal = np.asarray(Image.fromarray(rng.random((h // 2, 8)).astype(np.float32), 'F').resize((w, h), Image.BILINEAR))
    fibers = (vertical + horizontal) * 0.5 - 0.5
    large = fbm(h, w, 2.0, rng, 4) - 0.5
    color = np.ones((h, w, 3), np.float32) * rgb(base)
    return color * (1.0 + (fibers * amount + large * 0.05)[..., None])


def leaf_mask(w: int, h: int, rng: np.random.Generator, branches: int, scale: float) -> np.ndarray:
    img = Image.new('L', (w, h), 0)
    draw = ImageDraw.Draw(img)
    for _ in range(branches):
        x, y = w * rng.uniform(0.55, 1.05), -h * 0.05
        angle = math.radians(rng.uniform(105, 135))
        step = min(w, h) * 0.035 * scale
        side = 1
        for i in range(26):
            angle += math.radians(rng.uniform(-6, 6))
            nx_, ny_ = x + math.cos(angle) * step, y + math.sin(angle) * step
            draw.line([(x, y), (nx_, ny_)], fill=255, width=max(2, int(4 * scale)))
            x, y = nx_, ny_
            if i % 2 == 0 and i > 1:
                length = min(w, h) * rng.uniform(0.11, 0.17) * scale
                width = length * rng.uniform(0.28, 0.36)
                leaf_angle = angle + side * math.radians(rng.uniform(35, 60))
                side *= -1
                pts = []
                for t in np.linspace(0, 1, 24):
                    pts.append((t * length, width * math.sin(math.pi * t) ** 0.85))
                for t in np.linspace(1, 0, 24):
                    pts.append((t * length, -width * math.sin(math.pi * t) ** 0.85))
                ca, sa = math.cos(leaf_angle), math.sin(leaf_angle)
                draw.polygon([(x + px * ca - py * sa, y + px * sa + py * ca) for px, py in pts], fill=255)
            if not (-0.2 * w < x < 1.2 * w and y < 1.2 * h):
                break
    return np.asarray(img, np.float32) / 255.0


def leaf_shadows(w: int, h: int, seed: int, base: str, shade: str, light: str, branches: int = 3,
                 scale: float = 1.0, opacity: float = 0.34) -> np.ndarray:
    """窓辺の植物の影が落ちた麻布"""
    rng = np.random.default_rng(seed)
    color = linen(h, w, rng, base)
    mask = leaf_mask(w, h, rng, branches, scale)
    shadow = blur(mask, min(w, h) * 0.006) * 0.45 + blur(mask, min(w, h) * 0.03) * 0.55
    color = mix(color, color * rgb(shade), (shadow * opacity)[..., None] * 2.2)
    xx, yy = grid(h, w)
    glow = np.exp(-(((xx / w - 0.18) ** 2) / 0.10 + ((yy / h - 0.2) ** 2) / 0.16))
    color = mix(color, rgb(light), (glow * 0.22)[..., None])
    return color


def pebbles(w: int, h: int, seed: int, ground: str, stones: list[tuple[float, float, float, float, str]]) -> np.ndarray:
    """平たい石を重ねた静物（マットな質感）"""
    rng = np.random.default_rng(seed)
    color = linen(h, w, rng, ground, 0.02)
    xx, yy = grid(h, w)
    light = np.array([-0.4, -0.75, 0.52], np.float32)
    light /= np.linalg.norm(light)
    for cx, cy, rx, ry, tint in stones:
        cx, cy, rx, ry = cx * w, cy * h, rx * w, ry * h
        sh = (((xx - cx - rx * 0.12) / (rx * 1.05)) ** 2 + ((yy - cy - ry * 0.55) / (ry * 0.9)) ** 2) <= 1
        color = color * (1 - blur(sh.astype(np.float32), ry * 0.45)[..., None] * 0.25)
        dx, dy = (xx - cx) / rx, (yy - cy) / ry
        rr = dx * dx + dy * dy
        nz = np.sqrt(np.clip(1 - rr, 0, 1)) * 0.6
        norm = np.sqrt(dx * dx + dy * dy + nz * nz) + 1e-6
        diffuse = np.clip((dx * light[0] + dy * light[1] + nz * light[2]) / norm, 0, 1)
        speck = fbm(h, w, 40.0, rng, 2) * 0.05
        stone = rgb(tint) * (0.62 + 0.42 * diffuse[..., None]) * (0.97 + speck[..., None])
        edge = np.clip((1 - np.sqrt(np.clip(rr, 0, None))) * min(rx, ry), 0, 1) * (rr <= 1)
        color = mix(color, stone, edge[..., None])
    return color


def beams(w: int, h: int, seed: int, base: str, beam: str, tint: str) -> np.ndarray:
    """明るい地に斜めの光の帯（LP のファーストビュー背景）"""
    rng = np.random.default_rng(seed)
    color = glaze(w, h, seed, base, tint, base, 0.10)
    img = Image.new('L', (w, h), 0)
    draw = ImageDraw.Draw(img)
    for i, alpha in enumerate((255, 170, 120)):
        x0 = w * (0.52 + i * 0.12)
        draw.polygon([(x0, 0), (x0 + w * 0.10, 0), (x0 - w * 0.22, h), (x0 - w * 0.34, h)], fill=alpha)
    band = blur(np.asarray(img, np.float32) / 255.0, w * 0.035)
    color = mix(color, rgb(beam), (band * 0.16)[..., None])
    return color + (fbm(h, w, 2.4, rng, 3)[..., None] - 0.5) * 0.015


# ---------------------------------------------------------------------------
# サイトごとの出力
# ---------------------------------------------------------------------------

def skin_clinic() -> None:
    site = 'skin-clinic'
    save(to_image(glaze(1920, 1200, 11, '#F7F8F6', '#2B4C8C', '#E4ECE8')), site, 'hero-glaze.webp', 92)
    save(to_image(glaze(1200, 800, 12, '#F7F8F6', '#2B4C8C', '#DCE3E6', 0.14)), site, 'glaze-soft.webp', 92)
    cards = {
        'pearl-spots.webp': ('#F6F2EE', '#E9E2DC', '#F1E7E2'),
        'pearl-firmness.webp': ('#EEF3F1', '#DCE6E2', '#E3EEEA'),
        'pearl-pores.webp': ('#EEF2F5', '#DCE3E8', '#E6EDF2'),
        'pearl-hair.webp': ('#EDF0F7', '#D9DFEC', '#E4E9F4'),
    }
    for i, (name, (top, bottom, tint)) in enumerate(cards.items()):
        layout = [(0.40, 0.48, 0.20, tint), (0.62, 0.58, 0.12, tint), (0.27, 0.66, 0.08, tint)]
        save(to_image(pearls(960, 720, 20 + i, top, bottom, layout)), site, name, 78)
    save(to_image(interior(1200, 800, 31, '#EEF1F1', '#D9DEDF', '#FFFFFF', '#B9C6CC')), site, 'interior.webp', 90)
    save(to_image(interior(800, 1000, 32, '#EDF0F0', '#D5DBDD', '#FFFFFF', '#AFBEC6')), site, 'portrait-frame.webp', 90)


def surgery_clinic() -> None:
    site = 'surgery-clinic'
    save(to_image(silk(1920, 1200, 41, '#0E1115', '#2A3038', '#C3A574')), site, 'hero-silk.webp', 76)
    save(to_image(silk(1200, 900, 42, '#101318', '#30363F', '#C3A574', 11.0)), site, 'silk-detail.webp', 76)
    save(to_image(silk(1200, 900, 43, '#15181D', '#3A3631', '#D2B98E', 6.0)), site, 'silk-warm.webp', 76)
    save(to_image(interior(1200, 800, 44, '#23272D', '#191C20', '#5C5A55', '#3A3F46')), site, 'interior.webp', 90)
    save(to_image(interior(800, 1000, 45, '#22262B', '#181B1F', '#5A5853', '#383D44')), site, 'portrait-frame.webp', 90)


def esthetic() -> None:
    site = 'esthetic'
    save(to_image(leaf_shadows(1920, 1200, 51, '#ECEEE6', '#AEB8A0', '#FFFDF6', 3, 1.25)), site, 'hero-leaves.webp', 76)
    save(to_image(leaf_shadows(1200, 900, 52, '#E7E9E0', '#A5AF97', '#FFFDF6', 2, 1.1)), site, 'leaves-room.webp', 76)
    save(to_image(leaf_shadows(1200, 900, 53, '#EDE6EA', '#B8A3B1', '#FFFBFD', 2, 1.0, 0.28)), site, 'leaves-mauve.webp', 76)
    stones = [(0.42, 0.62, 0.20, 0.10, '#C9C6BC'), (0.55, 0.50, 0.15, 0.075, '#A9B39C'),
              (0.47, 0.39, 0.10, 0.05, '#D9D2D6')]
    save(to_image(pebbles(1200, 900, 54, '#E9EBE3', stones)), site, 'stones.webp', 76)
    save(to_image(interior(1200, 800, 55, '#E8EAE2', '#D3D7CB', '#FFFDF4', '#9AA68D')), site, 'room.webp', 90)


def lp() -> None:
    site = 'lp'
    save(to_image(beams(1600, 1000, 61, '#F5F7FB', '#2346A0', '#DCE6F7')), site, 'fv-bg.webp', 92)
    save(to_image(glaze(1200, 800, 62, '#F7F8FB', '#2346A0', '#E3ECFF', 0.12)), site, 'section-bg.webp', 92)


RECIPES = {'skin-clinic': skin_clinic, 'surgery-clinic': surgery_clinic, 'esthetic': esthetic, 'lp': lp}

if __name__ == '__main__':
    targets = sys.argv[1:] or list(RECIPES)
    for target in targets:
        print(target)
        RECIPES[target]()
