#!/usr/bin/env python3
"""Generate WeChat tabBar PNGs (81x81) — stdlib only. Matches App colors #8E95A3 / #7A57D1."""
from __future__ import annotations

import struct
import zlib
from pathlib import Path

W = H = 81
INACTIVE = (142, 149, 163, 255)
ACTIVE = (122, 87, 209, 255)
TRANSPARENT = (0, 0, 0, 0)


def buf_clear(buf: bytearray, rgba: tuple[int, int, int, int]) -> None:
    r, g, b, a = rgba
    for i in range(0, len(buf), 4):
        buf[i : i + 4] = bytes([r, g, b, a])


def px(buf: bytearray, x: int, y: int, rgba: tuple[int, int, int, int]) -> None:
    if 0 <= x < W and 0 <= y < H:
        i = (y * W + x) * 4
        buf[i : i + 4] = bytes(rgba)


def fill_rect(buf: bytearray, x0: int, y0: int, x1: int, y1: int, rgba: tuple) -> None:
    for y in range(max(0, y0), min(H, y1)):
        for x in range(max(0, x0), min(W, x1)):
            px(buf, x, y, rgba)


def thick_seg(buf: bytearray, x0: int, y0: int, x1: int, y1: int, rgba: tuple, th: int = 2) -> None:
    """Integer Bresenham + square brush."""
    dx = abs(x1 - x0)
    dy = -abs(y1 - y0)
    sx = 1 if x0 < x1 else -1
    sy = 1 if y0 < y1 else -1
    err = dx + dy
    x, y = x0, y0
    while True:
        for ox in range(-th, th + 1):
            for oy in range(-th, th + 1):
                px(buf, x + ox, y + oy, rgba)
        if x == x1 and y == y1:
            break
        e2 = 2 * err
        if e2 >= dy:
            err += dy
            x += sx
        if e2 <= dx:
            err += dx
            y += sy


def icon_home(buf: bytearray, color: tuple) -> None:
    buf_clear(buf, TRANSPARENT)
    thick_seg(buf, 40, 22, 22, 40, color, 2)
    thick_seg(buf, 40, 22, 58, 40, color, 2)
    thick_seg(buf, 22, 40, 58, 40, color, 2)
    fill_rect(buf, 28, 40, 52, 58, color)
    fill_rect(buf, 36, 48, 44, 58, TRANSPARENT)


def icon_zap(buf: bytearray, color: tuple) -> None:
    buf_clear(buf, TRANSPARENT)
    pts = [(46, 20), (32, 42), (44, 42), (34, 64), (52, 36), (42, 36), (50, 20)]
    for i in range(len(pts) - 1):
        thick_seg(buf, pts[i][0], pts[i][1], pts[i + 1][0], pts[i + 1][1], color, 2)


def icon_grid(buf: bytearray, color: tuple) -> None:
    buf_clear(buf, TRANSPARENT)
    # 2×2 cells (aligns with Web “layoutGrid” metaphor)
    fill_rect(buf, 12, 12, 36, 36, color)
    fill_rect(buf, 44, 12, 68, 36, color)
    fill_rect(buf, 12, 44, 36, 68, color)
    fill_rect(buf, 44, 44, 68, 68, color)


def icon_user(buf: bytearray, color: tuple) -> None:
    buf_clear(buf, TRANSPARENT)
    cx, cy, r = 40, 30, 9
    for y in range(H):
        for x in range(W):
            d = ((x - cx) ** 2 + (y - cy) ** 2) ** 0.5
            if 7 <= d <= r + 1:
                px(buf, x, y, color)
    for y in range(48, 66):
        half = int(10 + (y - 48) * 0.55)
        for x in range(40 - half, 40 + half):
            px(buf, x, y, color)


def write_png(path: Path, buf: bytearray) -> None:
    raw = bytearray()
    for y in range(H):
        raw.append(0)
        raw.extend(buf[y * W * 4 : (y + 1) * W * 4])
    compressed = zlib.compress(bytes(raw), 9)

    def chunk(tag: bytes, data: bytes) -> bytes:
        crc = zlib.crc32(tag + data) & 0xFFFFFFFF
        return struct.pack(">I", len(data)) + tag + data + struct.pack(">I", crc)

    ihdr = struct.pack(">IIBBBBB", W, H, 8, 6, 0, 0, 0)
    data = b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", ihdr) + chunk(b"IDAT", compressed) + chunk(b"IEND", b"")
    path.write_bytes(data)


def main() -> None:
    out = Path(__file__).resolve().parent.parent / "src" / "static" / "tabbar"
    out.mkdir(parents=True, exist_ok=True)
    icons = [
        ("home", icon_home),
        ("eat", icon_zap),
        ("plaza", icon_grid),
        ("me", icon_user),
    ]
    for name, draw in icons:
        for suffix, col in (("", INACTIVE), ("-active", ACTIVE)):
            buf = bytearray(W * H * 4)
            draw(buf, col)
            p = out / f"{name}{suffix}.png"
            write_png(p, buf)
            print("wrote", p)


if __name__ == "__main__":
    main()
